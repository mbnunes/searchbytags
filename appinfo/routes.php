<?php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#viewResult', 'url' => '/result', 'verb' => 'GET'],
        ['name' => 'search#getAllTags', 'url' => '/search/getAllTags', 'verb' => 'GET'],
        ['name' => 'search#searchByTag', 'url' => '/api/search', 'verb' => 'GET'],
    ]
];


