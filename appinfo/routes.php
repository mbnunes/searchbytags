<?php
return [
    'routes' => [
        // Rota principal
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        
        // Rota alternativa para garantir
        ['name' => 'page#main', 'url' => '/main', 'verb' => 'GET'],
        
        // APIs
        ['name' => 'search#getAllTags', 'url' => '/api/tags', 'verb' => 'GET'],
        ['name' => 'search#searchByTag', 'url' => '/api/search', 'verb' => 'GET']
    ]
];