<?php
declare(strict_types=1);

return [
    'routes' => [
        [
            'name' => 'page#index',
            'url' => '/',
            'verb' => 'GET'
        ],
        [
            'name' => 'search#getAllTags',
            'url' => '/api/tags',
            'verb' => 'GET'
        ],
        [
            'name' => 'search#searchByTag',
            'url' => '/api/search',
            'verb' => 'GET'
        ]
    ]
];