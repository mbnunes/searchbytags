<?php

namespace OCA\TagSearch\Search;

use OCP\Search\ISearchProvider;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\ITagManager;
use OCP\IUserSession;

class TagSearchProvider implements ISearchProvider {
    private ITagManager $tagManager;
    private IUserSession $userSession;

    public function __construct(ITagManager $tagManager, IUserSession $userSession) {
        $this->tagManager = $tagManager;
        $this->userSession = $userSession;
    }

    public function getId(): string {
        return 'tagsearch';
    }

    public function getName(): string {
        return 'Busca por Etiquetas';
    }

    public function search(string $searchTerm, int $limit = 30, int $offset = 0): SearchResult {
        if (stripos($searchTerm, 'etq:') !== 0) {
            return new SearchResult([]);
        }

        $user = $this->userSession->getUser();
        if (!$user) {
            return new SearchResult([]);
        }

        // Extrai as tags da string
        $tagString = trim(substr($searchTerm, strlen('etq:')));
        $tagNames = preg_split('/\s+/', $tagString);

        if (empty($tagNames)) {
            return new SearchResult([]);
        }

        // Busca por arquivos que tenham todas as tags (AND)
        $taggedFiles = null;
        foreach ($tagNames as $name) {
            $tags = $this->tagManager->getTagsByName($user, $name);
            if (empty($tags)) {
                return new SearchResult([]); // se não achou a tag, retorna vazio
            }

            $tagged = $this->tagManager->getObjectsForTags($tags, 'files');
            $ids = array_map(fn($t) => $t['id'], $tagged);

            if (is_null($taggedFiles)) {
                $taggedFiles = $ids;
            } else {
                $taggedFiles = array_intersect($taggedFiles, $ids); // AND
            }

            if (empty($taggedFiles)) {
                return new SearchResult([]); // nenhuma interseção
            }
        }

        // Cria os resultados
        $results = array_map(function ($fileId) use ($user) {
            $node = \OC::$server->getRootFolder()->getUserFolder($user->getUID())->getById($fileId)[0] ?? null;
            if (!$node) return null;

            return new SearchResultEntry(
                'tagsearch',
                $node->getName(),
                $node->getPath(),
                'Arquivo com tags',
                50
            );
        }, $taggedFiles);

        return new SearchResult(array_filter($results));
    }
}
