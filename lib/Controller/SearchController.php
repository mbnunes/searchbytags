<?php
namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\IURLGenerator;

class SearchController extends Controller {
    private IRootFolder $rootFolder;
    private ISystemTagManager $tagManager;
    private ISystemTagObjectMapper $tagMapper;
    private IURLGenerator $urlGenerator;
    private string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        IRootFolder $rootFolder,
        ISystemTagManager $tagManager,
        ISystemTagObjectMapper $tagMapper,
        IURLGenerator $urlGenerator,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->tagManager = $tagManager;
        $this->tagMapper = $tagMapper;
        $this->urlGenerator = $urlGenerator;
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
                $tagList[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'userVisible' => $tag->isUserVisible(),
                    'userAssignable' => $tag->isUserAssignable()
                ];
            }
            
            return new JSONResponse(['tags' => $tagList]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function searchByTag(): JSONResponse {
        $tags = $this->request->getParam('tags', '');
        
        if (empty($tags)) {
            return new JSONResponse(['files' => []]);
        }
        
        $tagNames = explode(',', $tags);
        
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $allTags = $this->tagManager->getAllTags(true);
            $results = [];
            $fileIds = [];
            
            // Encontra os IDs das tags
            $tagIds = [];
            foreach ($allTags as $tag) {
                if (in_array($tag->getName(), $tagNames)) {
                    $tagIds[] = $tag->getId();
                }
            }
            
            // Busca arquivos para cada tag
            foreach ($tagIds as $tagId) {
                $objectIds = $this->tagMapper->getObjectIdsForTags($tagId, 'files');
                foreach ($objectIds as $objectId) {
                    if (!in_array($objectId, $fileIds)) {
                        $fileIds[] = $objectId;
                    }
                }
            }
            
            // Obtém informações dos arquivos
            foreach ($fileIds as $fileId) {
                try {
                    $nodes = $userFolder->getById($fileId);
                    if (!empty($nodes)) {
                        $node = $nodes[0];
                        
                        // Obtém todas as tags do arquivo
                        $fileTags = [];
                        $tagIdsForFile = $this->tagMapper->getTagIdsForObjects([$fileId], 'files');
                        if (isset($tagIdsForFile[$fileId])) {
                            foreach ($tagIdsForFile[$fileId] as $tid) {
                                $tag = $this->tagManager->getTagsByIds([$tid]);
                                if (!empty($tag)) {
                                    $fileTags[] = $tag[0]->getName();
                                }
                            }
                        }
                        
                        $relativePath = $userFolder->getRelativePath($node->getPath());
                        $results[] = [
                            'id' => $node->getId(),
                            'name' => $node->getName(),
                            'path' => $relativePath,
                            'tags' => $fileTags,
                            'size' => $node->getSize(),
                            'mtime' => $node->getMTime(),
                            'mimetype' => $node->getMimeType(),
                            'type' => $node->getType() === \OCP\Files\FileInfo::TYPE_FOLDER ? 'folder' : 'file',
                            'url' => $this->urlGenerator->linkToRoute('files.view.index', [
                                'dir' => dirname($relativePath),
                                'scrollto' => basename($relativePath)
                            ])
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            return new JSONResponse(['files' => $results]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }
}