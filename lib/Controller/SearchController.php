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

class SearchController extends Controller
{
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
    public function getAllTags(): JSONResponse
    {
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
    public function searchByTag(): JSONResponse
    {
        $query = $this->request->getParam('query', '');
        $this->logger->info('Hybrid Search: Query: ' . $query);


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

                        // Coleta todos os metadados necessários
                        $fileData = [
                            'id' => $node->getId(),
                            'name' => $node->getName(),
                            'path' => dirname($relativePath),
                            'tags' => $fileTags,
                            'size' => $node->getSize(),
                            'mtime' => $node->getMTime(),
                            'mime' => $node->getMimeType(),
                            'mimetype' => $node->getMimeType(), // compatibilidade
                            'isImage' => str_starts_with($node->getMimeType(), 'image/'),
                            'isVideo' => str_starts_with($node->getMimeType(), 'video/'),
                            'type' => $node->getType() === \OCP\Files\FileInfo::TYPE_FOLDER ? 'folder' : 'file',
                            'etag' => $node->getEtag(),
                            'permissions' => $node->getPermissions(),
                            'owner' => $node->getOwner() ? $node->getOwner()->getUID() : $this->userId,
                            'url' => $this->urlGenerator->linkToRoute('files.view.index', [
                                'dir' => dirname($relativePath),
                                'openfile' => $node->getId()
                            ])
                        ];

                        // Adiciona metadados extras se disponíveis
                        if (method_exists($node, 'getCreationTime')) {
                            $fileData['creationTime'] = $node->getCreationTime();
                        }

                        if (method_exists($node, 'getUploadTime')) {
                            $fileData['uploadTime'] = $node->getUploadTime();
                        }

                        // Informações específicas para imagens
                        if (str_starts_with($node->getMimeType(), 'image/')) {
                            $fileData['isImage'] = true;

                            // Tenta obter dimensões da imagem se possível
                            try {
                                $content = $node->getContent();
                                if ($content && function_exists('getimagesizefromstring')) {
                                    $imageInfo = @getimagesizefromstring($content);
                                    if ($imageInfo) {
                                        $fileData['width'] = $imageInfo[0];
                                        $fileData['height'] = $imageInfo[1];
                                    }
                                }
                            } catch (\Exception $e) {
                                // Ignora erro ao tentar obter dimensões
                                $this->logger->debug('Could not get image dimensions: ' . $e->getMessage());
                            }
                        }

                        $results[] = $fileData;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Error processing file ' . $fileId . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Ordena por nome
            usort($results, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            return new JSONResponse(['files' => $results]);
        } catch (\Exception $e) {
            $this->logger->error('Tags Search: Error: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function parseSearchQuery(string $query): array
    {
        // Verifica se há algum # na query
        $hasHashTags = strpos($query, '#') !== false;

        // Separa por OR primeiro
        $orParts = preg_split('/\s+OR\s+/i', $query);

        $parsedParts = [];
        foreach ($orParts as $orPart) {
            // Separa por AND
            $andParts = preg_split('/\s+AND\s+/i', $orPart);
            $andTags = [];
            $andNames = [];

            foreach ($andParts as $part) {
                $part = trim($part);
                if (empty($part)) continue;

                // Se começar com #, é uma tag
                if (str_starts_with($part, '#')) {
                    $tagName = substr($part, 1); // Remove o #
                    if (!empty($tagName)) {
                        $andTags[] = $tagName;
                    }
                } else {
                    // Se não há # na query inteira, trata como tag (compatibilidade)
                    if (!$hasHashTags) {
                        $andTags[] = $part;
                    } else {
                        // Senão, é um nome de arquivo
                        $andNames[] = $part;
                    }
                }
            }

            if (!empty($andTags) || !empty($andNames)) {
                $parsedParts[] = [
                    'type' => 'AND',
                    'tags' => $andTags,
                    'names' => $andNames
                ];
            }
        }

        return [
            'type' => 'OR',
            'groups' => $parsedParts
        ];
    }

    private function executeSearch(array $parsedQuery, array $tagNameToId): array
    {
        $allFileIds = [];

        // Para cada grupo OR
        foreach ($parsedQuery['groups'] as $group) {
            if ($group['type'] === 'AND') {
                $groupFileIds = null;
                $hasValidCriteria = false;

                // Busca por tags
                if (!empty($group['tags'])) {
                    foreach ($group['tags'] as $tagName) {
                        $tagName = strtolower(trim($tagName));

                        if (!isset($tagNameToId[$tagName])) {
                            $this->logger->info('Tag not found: ' . $tagName);
                            $groupFileIds = []; // Se uma tag não existe, grupo retorna vazio
                            break;
                        }

                        $tagId = $tagNameToId[$tagName];
                        $tagFileIds = $this->tagMapper->getObjectIdsForTags([$tagId], 'files');

                        if ($groupFileIds === null) {
                            $groupFileIds = $tagFileIds;
                        } else {
                            $groupFileIds = array_intersect($groupFileIds, $tagFileIds);
                        }

                        $hasValidCriteria = true;
                    }
                }

                // Busca por nomes de arquivos
                if (!empty($group['names'])) {
                    foreach ($group['names'] as $fileName) {
                        $nameFileIds = $this->searchByFileName($fileName);

                        if ($groupFileIds === null) {
                            $groupFileIds = $nameFileIds;
                        } else {
                            $groupFileIds = array_intersect($groupFileIds, $nameFileIds);
                        }

                        $hasValidCriteria = true;
                    }
                }

                // Se teve critérios válidos e encontrou arquivos
                if ($hasValidCriteria && !empty($groupFileIds)) {
                    $allFileIds = array_merge($allFileIds, $groupFileIds);
                }
            }
        }

        return array_unique($allFileIds);
    }

    private function searchByFileName(string $fileName): array
    {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $fileIds = [];

            // Usa a busca nativa do Nextcloud para ser mais eficiente
            $searchResults = $userFolder->search($fileName);

            foreach ($searchResults as $node) {
                if ($node->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                    // Verifica se o nome contém o termo buscado (case-insensitive)
                    if (stripos($node->getName(), $fileName) !== false) {
                        $fileIds[] = $node->getId();
                    }
                }
            }

            $this->logger->info('Filename search for "' . $fileName . '" found ' . count($fileIds) . ' files');
            return $fileIds;
        } catch (\Exception $e) {
            $this->logger->error('Error searching by filename: ' . $e->getMessage());
            return [];
        }
    }

    private function searchInFolder($folder, string $fileName, array &$fileIds): void
    {
        try {
            $nodes = $folder->getDirectoryListing();

            foreach ($nodes as $node) {
                if ($node->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                    // Busca case-insensitive
                    if (stripos($node->getName(), $fileName) !== false) {
                        $fileIds[] = $node->getId();
                    }
                } elseif ($node->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                    // Recursivamente busca em subpastas
                    $this->searchInFolder($node, $fileName, $fileIds);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error searching in folder: ' . $e->getMessage());
        }
    }
}
