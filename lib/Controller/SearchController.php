<?php
namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;

class SearchController extends Controller {
    private IRootFolder $rootFolder;
    private ISystemTagManager $tagManager;
    private ISystemTagObjectMapper $tagMapper;
    private string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        IRootFolder $rootFolder,
        ISystemTagManager $tagManager,
        ISystemTagObjectMapper $tagMapper,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->tagManager = $tagManager;
        $this->tagMapper = $tagMapper;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     */
    public function searchByTag(string $tagName): JSONResponse {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $tags = $this->tagManager->getAllTags(true);
            
            $results = [];
            foreach ($tags as $tag) {
                if (stripos($tag->getName(), $tagName) !== false) {
                    $objectIds = $this->tagMapper->getObjectIdsForTags($tag->getId(), 'files');
                    foreach ($objectIds as $objectId) {
                        try {
                            $nodes = $userFolder->getById($objectId);
                            if (!empty($nodes)) {
                                $node = $nodes[0];
                                $results[] = [
                                    'id' => $node->getId(),
                                    'name' => $node->getName(),
                                    'path' => $userFolder->getRelativePath($node->getPath()),
                                    'tags' => [$tag->getName()],
                                    'size' => $node->getSize(),
                                    'mtime' => $node->getMTime()
                                ];
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
            
            return new JSONResponse(['files' => $results]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }
}