<?php

namespace OCA\TagSearch\AppInfo;

use OCP\AppFramework\App;
use OCA\TagSearch\Search\TagSearchProvider;
use OCP\Search\ISearchProvider;
use OCP\IUserSession;
use OCP\ITagManager;
use OCP\AppFramework\Bootstrap\IBootContext;

class Application extends App {
    public const APP_ID = 'search_by_tags';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        $container->registerService(ISearchProvider::class, function ($c) {
            return new TagSearchProvider(
                $c->query(ITagManager::class),
                $c->query(IUserSession::class)
            );
        });
    }
}
