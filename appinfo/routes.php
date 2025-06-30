<?php

\OCP\Util::addScript('viewer', 'viewer-main'); // carrega o OCA.Viewer

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#viewResult', 'url' => '/result', 'verb' => 'GET'],
        ['name' => 'search#getAllTags', 'url' => '/api/tags', 'verb' => 'GET'],
        ['name' => 'search#searchByTag', 'url' => '/api/search', 'verb' => 'GET'],
    ]
];


