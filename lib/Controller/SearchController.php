<?php
namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\IURLGenerator;
use OCP\ILogger;

class SearchController extends Controller {
    private IRootFolder $rootFolder;
    private ISystemTagManager $tagManager;
    private ISystemTagObjectMapper $tagMapper;
    private IURLGenerator $urlGenerator;
    private ILogger $logger;
    private string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        IRootFolder $rootFolder,
        ISystemTagManager $tagManager,
        ISystemTagObjectMapper $tagMapper,
        IURLGenerator $urlGenerator,
        ILogger $logger,
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
     * @NoAdminRequired
     */
    public function searchByTag(): JSONResponse {
        $query = $this->request->getParam('query', '');
        if (empty($query)) {
            return new JSONResponse(['files' => []]);
        }
        try {
            $parsedQuery = $this->parseSearchQuery($query);
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $allTags = $this->tagManager->getAllTags(true);
            $tagNameToId = [];
            foreach ($allTags as $tag) {
                $tagNameToId[$tag->getName()] = $tag->getId();
            }
            $fileIds = $this->executeSearch($parsedQuery, $tagNameToId);
            if (empty($fileIds)) {
                return new JSONResponse(['files' => []]);
            }
            $results = [];
            foreach ($fileIds as $fileId) {
                $nodes = $userFolder->getById($fileId);
                if (!empty($nodes)) {
                    $node = $nodes[0];
                    if (!$node->isReadable()) {
                        continue;
                    }
                    $relativePath = $userFolder->getRelativePath($node->getPath());
                    $results[] = [
                        'id' => $node->getId(),
                        'name' => $node->getName(),
                        'path' => dirname($relativePath),
                        'url' => $this->urlGenerator->getAbsoluteURL(
                            '/index.php/apps/files?dir=' . urlencode(dirname($relativePath)) . '&scrollto=' . urlencode($node->getName())
                        )
                    ];
                }
            }
            return new JSONResponse(['files' => $results]);
        } catch (\Exception $e) {
            $this->logger->error('Search error: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function parseSearchQuery(string $query): array {
        $orParts = preg_split('/\s+OR\s+/i', $query);
        $parsedParts = [];
        foreach ($orParts as $orPart) {
            $andParts = preg_split('/\s+AND\s+/i', $orPart);
            $andTags = array_filter(array_map('trim', $andParts));
            if (!empty($andTags)) {
                $parsedParts[] = [
                    'type' => 'AND',
                    'tags' => $andTags
                ];
            }
        }
        return ['type' => 'OR', 'groups' => $parsedParts];
    }

    private function executeSearch(array $parsedQuery, array $tagNameToId): array {
        $allFileIds = [];
        foreach ($parsedQuery['groups'] as $group) {
            if ($group['type'] === 'AND') {
                $groupFileIds = null;
                foreach ($group['tags'] as $tagName) {
                    if (!isset($tagNameToId[$tagName])) {
                        $groupFileIds = [];
                        break;
                    }
                    $tagId = $tagNameToId[$tagName];
                    $tagFileIds = $this->tagMapper->getObjectIdsForTags([$tagId], 'files');
                    if ($groupFileIds === null) {
                        $groupFileIds = $tagFileIds;
                    } else {
                        $groupFileIds = array_intersect($groupFileIds, $tagFileIds);
                    }
                }
                if (!empty($groupFileIds)) {
                    $allFileIds = array_merge($allFileIds, $groupFileIds);
                }
            }
        }
        return array_unique($allFileIds);
    }
}
