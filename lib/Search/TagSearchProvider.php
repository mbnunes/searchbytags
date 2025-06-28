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

        $query = trim($query);

        // Detectar modo: AND ou OR
        $isAndMode = false;
        if (stripos($query, 'tag:') === 0) {
            $isAndMode = true;
            $query = trim(substr($query, 4));
        } elseif (stripos($query, 'tags:') === 0) {
            $query = trim(substr($query, 5));
        } else {
            // Não é uma busca por etiqueta
            return new SearchResult([]);
        }

        $words = preg_split('/\s+/', $query);
        $matchedTagIds = [];

        // Mapeia cada palavra para IDs de tags (parciais, case-insensitive)
        foreach ($words as $word) {
            foreach ($tags as $tag) {
                if (stripos($tag['name'], $word) !== false) {
                    $matchedTagIds[$word] = $tag['id'];
                    break;
                }
            }
        }

        // Se faltou casar alguma palavra com tag, aborta no modo AND
        if ($isAndMode && count($matchedTagIds) < count($words)) {
            return new SearchResult([]);
        }

        $entries = [];
        $rootFolder = \OC::$server->get(IRootFolder::class);
        $userFolder = $rootFolder->getUserFolder($user->getUID());

        if ($isAndMode) {
            // Modo AND: interseção
            $tagIds = array_values($matchedTagIds);
            $commonObjectIds = $tagManager->getObjectsForTags([$tagIds[0]], 'files');

            for ($i = 1; $i < count($tagIds); $i++) {
                $objectIds = $tagManager->getObjectsForTags([$tagIds[$i]], 'files');
                $commonObjectIds = array_intersect($commonObjectIds, $objectIds);
                if (empty($commonObjectIds)) {
                    return new SearchResult([]);
                }
            }

            foreach ($commonObjectIds as $fileId) {
                try {
                    $nodes = $userFolder->getById($fileId);
                    if (!empty($nodes)) {
                        $node = $nodes[0];
                        $entries[] = new SearchResultEntry(
                            $node->getName(),
                            $node->getPath(),
                            $node->getId(),
                            $node->getMTime(),
                            $node->getSize(),
                            '', '', ''
                        );
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        } else {
            // Modo OR: união
            $tagIds = array_values($matchedTagIds);
            $objectIds = $tagManager->getObjectsForTags($tagIds, 'files');

            foreach ($objectIds as $fileId) {
                try {
                    $nodes = $userFolder->getById($fileId);
                    if (!empty($nodes)) {
                        $node = $nodes[0];
                        $entries[] = new SearchResultEntry(
                            $node->getName(),
                            $node->getPath(),
                            $node->getId(),
                            $node->getMTime(),
                            $node->getSize(),
                            '', '', ''
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
