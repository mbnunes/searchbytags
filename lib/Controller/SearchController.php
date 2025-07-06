<?php
namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class SearchController extends Controller {
    private IRootFolder $rootFolder;
    private ISystemTagManager $tagManager;
    private ISystemTagObjectMapper $tagMapper;
    private IURLGenerator $urlGenerator;
    private LoggerInterface $logger;
    private string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        IRootFolder $rootFolder,
        ISystemTagManager $tagManager,
        ISystemTagObjectMapper $tagMapper,
        IURLGenerator $urlGenerator,
        LoggerInterface $logger,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->tagManager = $tagManager;
        $this->tagMapper = $tagMapper;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     */
    public function getAllTags(): JSONResponse {
        try {
            $tags = $this->tagManager->getAllTags(true);
            $tagList = [];
            
            foreach ($tags as $tag) {
                if ($tag->isUserVisible() && $tag->isUserAssignable()) {
                    $tagList[] = [
                        'id' => $tag->getId(),
                        'name' => $tag->getName()
                    ];
                }
            }
            
            return new JSONResponse(['tags' => $tagList]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function searchByTag(): JSONResponse {
    $query = $this->request->getParam('query', '');
    $this->logger->info('Tags Search: Query: ' . $query);

    if (empty($query)) {
        return new JSONResponse(['files' => []]);
    }

    try {
        // Parse da query
        $parsedQuery = $this->parseSearchQuery($query);
        $this->logger->info('Tags Search: Parsed query: ' . json_encode($parsedQuery));

        // Pega todas as tags disponíveis para o usuário
        $allTags = $this->tagManager->getAllTags(true);

        // Mapeia nomes de tags para IDs (com lowercase para evitar problemas de case)
        $tagNameToId = [];
        foreach ($allTags as $tag) {
            $tagNameToId[strtolower($tag->getName())] = $tag->getId();
        }

        // Ajusta os nomes das tags da query parseada para lowercase para casar com o map
        foreach ($parsedQuery['groups'] as &$group) {
            foreach ($group['tags'] as &$tagName) {
                $tagName = strtolower(trim($tagName));
            }
        }
        unset($group, $tagName); // limpeza da referência

        // Executa a busca com os IDs das tags já mapeados
        $fileIds = $this->executeSearch($parsedQuery, $tagNameToId);

        if (empty($fileIds)) {
            return new JSONResponse(['files' => []]);
        }

        // Pega o diretório raiz do usuário
        $userFolder = $this->rootFolder->getUserFolder($this->userId);

        $results = [];
        foreach ($fileIds as $fileId) {
            try {
                $nodes = $userFolder->getById($fileId);
                if (!empty($nodes)) {
                    $node = $nodes[0];
                    if (!$node->isReadable()) {
                        continue;
                    }

                    // Obtém todas as tags do arquivo
                    $fileTags = [];
                    $tagIdsForFile = $this->tagMapper->getTagIdsForObjects([$fileId], 'files');
                    if (isset($tagIdsForFile[$fileId])) {
                        foreach ($tagIdsForFile[$fileId] as $tid) {
                            try {
                                $tags = $this->tagManager->getTagsByIds([$tid]);
                                if (!empty($tags) && isset($tags[0]) && $tags[0] !== null) {
                                    $fileTags[] = $tags[0]->getName();
                                }
                            } catch (\Exception $e) {
                                $this->logger->warning('Erro ao obter tag: ' . $e->getMessage());
                                continue;
                            }
                        }
                    }

                    $relativePath = $userFolder->getRelativePath($node->getPath());
                    $results[] = [
                        'id' => $node->getId(),
                        'name' => $node->getName(),
                        'path' => dirname($relativePath),
                        'tags' => $fileTags,
                        'size' => $node->getSize(),
                        'mtime' => $node->getMTime(),
                        'mimetype' => $node->getMimeType(),
                        'type' => $node->getType() === \OCP\Files\FileInfo::TYPE_FOLDER ? 'folder' : 'file',
                        'url' => $this->urlGenerator->linkToRoute('files.view.index', [
                            'dir' => dirname($relativePath),
                            'scrollto' => $node->getName()
                        ])
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Ordena por nome
        usort($results, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return new JSONResponse(['files' => $results]);

    } catch (\Exception $e) {
        $this->logger->error('Tags Search: Error: ' . $e->getMessage());
        return new JSONResponse(['error' => $e->getMessage()], 500);
    }
}



    private function parseSearchQuery(string $query): array {
        // Converte para maiúsculas para facilitar o parsing
        $upperQuery = strtoupper($query);
        
        // Separa por OR primeiro
        $orParts = preg_split('/\s+OR\s+/i', $query);
        
        $parsedParts = [];
        foreach ($orParts as $orPart) {
            // Separa por AND
            $andParts = preg_split('/\s+AND\s+/i', $orPart);
            $andTags = array_map('trim', $andParts);
            $andTags = array_filter($andTags); // Remove vazios
            
            if (!empty($andTags)) {
                $parsedParts[] = [
                    'type' => 'AND',
                    'tags' => $andTags
                ];
            }
        }
        
        return [
            'type' => 'OR',
            'groups' => $parsedParts
        ];
    }
    
    private function executeSearch(array $parsedQuery, array $tagNameToId): array {
        $allFileIds = [];
        
        // Para cada grupo OR
        foreach ($parsedQuery['groups'] as $group) {
            if ($group['type'] === 'AND') {
                // Busca arquivos que tenham TODAS as tags do grupo
                $groupFileIds = null;
                
                foreach ($group['tags'] as $tagName) {
                    if (!isset($tagNameToId[$tagName])) {
                        $this->logger->info('Tag not found: ' . $tagName);
                        $groupFileIds = [];
                        break;
                    }
                    
                    $tagId = $tagNameToId[$tagName];
                    $tagFileIds = $this->tagMapper->getObjectIdsForTags([$tagId], 'files');
                    
                    if ($groupFileIds === null) {
                        $groupFileIds = $tagFileIds;
                    } else {
                        // Interseção - mantém apenas arquivos que têm todas as tags
                        $groupFileIds = array_intersect($groupFileIds, $tagFileIds);
                    }
                }
                
                if (!empty($groupFileIds)) {
                    // União com os resultados anteriores (OR)
                    $allFileIds = array_merge($allFileIds, $groupFileIds);
                }
            }
        }
        
        // Remove duplicatas
        return array_unique($allFileIds);
    }
}
