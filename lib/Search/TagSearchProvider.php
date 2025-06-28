<?php

namespace OCA\TagSearch\Search;

use OCP\Search\ISearchProvider;
use OCP\Search\ISearchResult;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\Files\IRootFolder;
use OCP\ITagManager;
use OCP\IUser;

class TagSearchProvider implements ISearchProvider {
    public function getId(): string {
        return 'tagsearch';
    }

    public function getName(): string {
        return 'Tag Search';
    }

    public function search(IUser $user, string $query, int $limit = null, int $offset = null): ISearchResult {
        /** @var ITagManager $tagManager */
        $tagManager = \OC::$server->getTagManager();
        $tags = $tagManager->getTagsForUser($user->getUID());
        $matchedTagIds = [];

        foreach ($tags as $tag) {
            if (stripos($tag['name'], $query) !== false) {
                $matchedTagIds[] = $tag['id'];
            }
        }

        $entries = [];
        if (!empty($matchedTagIds)) {
            $objectIds = $tagManager->getObjectsForTags($matchedTagIds, 'files');
            $rootFolder = \OC::$server->get(IRootFolder::class);
            $userFolder = $rootFolder->getUserFolder($user->getUID());

            foreach ($objectIds as $fileId) {
                try {
                    $node = $userFolder->getById($fileId)[0] ?? null;
                    if ($node) {
                        $entries[] = new SearchResultEntry(
                            $node->getName(),
                            $node->getPath(),
                            $node->getId(),
                            $node->getMTime(),
                            $node->getSize(),
                            '',
                            '',
                            ''
                        );
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return new SearchResult($entries);
    }
}
