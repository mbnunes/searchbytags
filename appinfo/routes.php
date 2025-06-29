<?php
return [
    'routes' => [
        ['name' => 'search#searchByTag', 'url' => '/api/search/{tagName}', 'verb' => 'GET'],
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET']
    ]
];