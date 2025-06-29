<?php
return [
    'routes' => [
        [
            'name' => 'page.index',
            'url' => '/',
            'verb' => 'GET',
            'controller' => 'PageController',
            'action' => 'index',
        ],
        [
            'name' => 'page.viewResult',
            'url' => '/view',
            'verb' => 'GET',
            'controller' => 'PageController',
            'action' => 'viewResult',
        ],
        [
            'name' => 'search.getAllTags',
            'url' => '/api/tags',
            'verb' => 'GET',
            'controller' => 'SearchController',
            'action' => 'getAllTags',
        ],
        [
            'name' => 'search.searchByTag',
            'url' => '/api/search',
            'verb' => 'GET',
            'controller' => 'SearchController',
            'action' => 'searchByTag',
        ],
    ]
];
