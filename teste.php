<?php
// search_by_tags/test.php
require_once __DIR__ . '/../../lib/base.php';

echo "Testing routes for search_by_tags app\n\n";

$urlGenerator = \OC::$server->getURLGenerator();

try {
    $url = $urlGenerator->linkToRoute('search_by_tags.page.index');
    echo "Success! URL: " . $url . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Lista todas as rotas registradas
$router = \OC::$server->getRouter();
$routes = $router->getRoutes();
echo "\nRegistered routes for search_by_tags:\n";
foreach ($routes as $name => $route) {
    if (strpos($name, 'search_by_tags') !== false) {
        echo "- " . $name . "\n";
    }
}