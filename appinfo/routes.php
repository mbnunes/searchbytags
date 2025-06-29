<?php
return [
    'routes' => [
        // Rota principal do app
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        
        // Rotas da API
        ['name' => 'search#searchByTag', 'url' => '/api/search', 'verb' => 'GET'],
        ['name' => 'search#getAllTags', 'url' => '/api/tags', 'verb' => 'GET']
    ]
];