<?php
namespace OCA\SearchByTags\Search;

use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Files\Node;

class TagsSearchProvider implements IProvider {
    private IURLGenerator $urlGenerator;
    private IL10N $l10n;
    private IRootFolder $rootFolder;
    private ISystemTagManager $tagManager;
    private ISystemTagObjectMapper $tagMapper;

    public function __construct(
        IURLGenerator $urlGenerator,
        IL10N $l10n,
        IRootFolder $rootFolder,
        ISystemTagManager $tagManager,
        ISystemTagObjectMapper $tagMapper
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
        $this->rootFolder = $rootFolder;
        $this->tagManager = $tagManager;
        $this->tagMapper = $tagMapper;
    }

    public function getId(): string {
        return 'tags';
    }

    public function getName(): string {
        return $this->l10n->t('Tags');
    }

    public function getOrder(string $route, array $routeParameters): int {
        return 25;
    }

    public function search(IUser $user, ISearchQuery $query): SearchResult {
        $searchTerm = $query->getTerm();
        $limit = $query->getLimit();
        $offset = $query->getCursor();

        // Busca tags que correspondem ao termo
        try {
            $tags = $this->tagManager->getAllTags(true);
            $matchingTags = array_filter($tags, function($tag) use ($searchTerm) {
                return stripos($tag->getName(), $searchTerm) !== false;
            });

            $results = [];
            $userFolder = $this->rootFolder->getUserFolder($user->getUID());

            foreach ($matchingTags as $tag) {
                // Busca arquivos com essa tag
                $objectIds = $this->tagMapper->getObjectIdsForTags($tag->getId(), 'files');
                
                foreach ($objectIds as $objectId) {
                    try {
                        $nodes = $userFolder->getById($objectId);
                        if (!empty($nodes)) {
                            $node = $nodes[0];
                            $results[] = $this->createSearchResultEntry($node, $tag->getName());
                        }
                    } catch (\Exception $e) {
                        // Ignora arquivos que não podem ser acessados
                        continue;
                    }
                }
            }

            // Também busca diretamente nos metadados dos arquivos
            $this->searchFilesWithTagContent($userFolder, $searchTerm, $results);

            // Aplica limite e offset
            $results = array_slice($results, $offset, $limit);

            return SearchResult::complete(
                $this->getName(),
                $results
            );
        } catch (\Exception $e) {
            return SearchResult::complete($this->getName(), []);
        }
    }

    private function searchFilesWithTagContent($folder, string $searchTerm, array &$results): void {
        try {
            $nodes = $folder->search($searchTerm);
            foreach ($nodes as $node) {
                if ($node instanceof Node) {
                    $tags = $this->tagMapper->getTagIdsForObjects([$node->getId()], 'files');
                    if (!empty($tags[$node->getId()])) {
                        $tagNames = [];
                        foreach ($tags[$node->getId()] as $tagId) {
                            try {
                                $tag = $this->tagManager->getTagsByIds([$tagId]);
                                if (!empty($tag)) {
                                    $tagNames[] = $tag[0]->getName();
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                        if (!empty($tagNames)) {
                            $results[] = $this->createSearchResultEntry($node, implode(', ', $tagNames));
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignora erros
        }
    }

    private function createSearchResultEntry(Node $node, string $tagInfo): SearchResultEntry {
        $path = $node->getPath();
        $userFolder = $this->rootFolder->getUserFolder($node->getOwner()->getUID());
        $relativePath = $userFolder->getRelativePath($path);

        return new SearchResultEntry(
            $this->urlGenerator->linkToRoute('files.view.index', [
                'dir' => dirname($relativePath),
                'scrollto' => basename($relativePath)
            ]),
            $node->getName(),
            $this->l10n->t('Tags: %s', [$tagInfo]),
            $this->urlGenerator->linkToRoute('files.view.index'),
            $node->getMimetype() === 'httpd/unix-directory' ? 'icon-folder' : 'icon-file'
        );
    }
}