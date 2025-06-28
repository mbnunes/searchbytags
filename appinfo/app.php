<?php

namespace OCA\TagSearch\AppInfo;

use OCP\AppFramework\App;
use OCP\Search\ISearchProvider;
use OCP\Search\SearchProviderEvent;
use OCA\TagSearch\Search\TagSearchProvider;

$app = new App('search_by_tags');
$container = $app->getContainer();
$dispatcher = $container->getServer()->getEventDispatcher();

$dispatcher->addListener(SearchProviderEvent::class, function (SearchProviderEvent $event) {
    $event->registerProvider(new TagSearchProvider());
});
