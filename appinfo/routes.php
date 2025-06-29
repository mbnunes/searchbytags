<?php
$application = new \OCA\SearchByTags\AppInfo\Application();
$application->registerRoutes($this, [
    'routes' => [
        ['name' => 'page#index', 'url' => '/apps/search_by_tags', 'verb' => 'GET'],
        ['name' => 'search#getAllTags', 'url' => '/api/tags', 'verb' => 'GET'],
        ['name' => 'search#searchByTag', 'url' => '/api/search', 'verb' => 'GET']
    ]
]);