<?php
// search_by_tags/test.php
require_once __DIR__ . '/../../lib/base.php';

echo "Testing routes for search_by_tags app\n\n";

$urlGenerator = \OC::$server->getURLGenerator();

try {
    // Testa diferentes formas de gerar a URL
    $url1 = $urlGenerator->linkToRoute('search_by_tags.page.index');
    echo "linkToRoute result: '" . $url1 . "'\n";
    
    $url2 = $urlGenerator->linkToRouteAbsolute('search_by_tags.page.index');
    echo "linkToRouteAbsolute result: '" . $url2 . "'\n";
    
    $url3 = $urlGenerator->linkTo('search_by_tags', 'index.php');
    echo "linkTo result: '" . $url3 . "'\n";
    
    $url4 = $urlGenerator->getAbsoluteURL('/index.php/apps/search_by_tags/');
    echo "Direct absolute URL: '" . $url4 . "'\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Verifica se o app está habilitado
$appManager = \OC::$server->getAppManager();
$isEnabled = $appManager->isEnabledForUser('search_by_tags');
echo "\nApp enabled: " . ($isEnabled ? 'Yes' : 'No') . "\n";

// Lista todas as rotas registradas
echo "\nAll registered routes containing 'search_by_tags':\n";
$router = \OC::$server->getRouter();
$collection = $router->getCollection();
foreach ($collection->all() as $name => $route) {
    if (strpos($name, 'search_by_tags') !== false) {
        echo "- Route: " . $name . "\n";
        echo "  Path: " . $route->getPath() . "\n";
        echo "  Methods: " . implode(', ', $route->getMethods()) . "\n\n";
    }
}