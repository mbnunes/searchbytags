<?php
// search_by_tags/debug.php
echo "=== DEBUG SEARCH_BY_TAGS APP ===\n\n";

// Verifica a estrutura de arquivos
$required_files = [
    'appinfo/info.xml',
    'appinfo/routes.php',
    'appinfo/app.php',
    'lib/AppInfo/Application.php',
    'lib/Controller/PageController.php',
    'lib/Controller/SearchController.php',
    'templates/main.php'
];

echo "1. Verificando arquivos:\n";
foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "   - $file: " . (file_exists($path) ? "OK" : "FALTANDO") . "\n";
}

// Verifica o conteúdo do info.xml
echo "\n2. Conteúdo do info.xml:\n";
$info_content = file_get_contents(__DIR__ . '/appinfo/info.xml');
echo substr($info_content, 0, 500) . "...\n";

// Verifica namespaces
echo "\n3. Verificando namespace no Application.php:\n";
$app_content = file_get_contents(__DIR__ . '/lib/AppInfo/Application.php');
preg_match('/namespace\s+([^;]+);/', $app_content, $matches);
echo "   Namespace: " . ($matches[1] ?? 'NÃO ENCONTRADO') . "\n";

// Verifica o app.php
echo "\n4. Conteúdo do app.php:\n";
echo file_get_contents(__DIR__ . '/appinfo/app.php') . "\n";