<?php
script('search_by_tags', 'result');
style('files', 'files'); // Usa os estilos do Files

$application = \OC::$server->query(\OCP\INavigationManager::class);
$application->add(['id' => 'search_by_tags', 'order' => 10, 'href' => \OC::$server->getURLGenerator()->linkToRoute('search_by_tags.page.index'), 'icon' => \OC::$server->getURLGenerator()->imagePath('search_by_tags', 'app.svg'), 'name' => 'Busca por Tags']);
?>

<div class="section" style="padding: 2em;">
    <h2>Busca por Tags</h2>

    <input type="text" id="tag-input" placeholder="Digite uma ou mais tags..." style="width: 100%; padding: 0.5em; margin-bottom: 1em; border: 1px solid #ccc; border-radius: 4px;" list="tag-suggestions">
    <datalist id="tag-suggestions"></datalist>

    <ul id="tag-results" class="fileListView"></ul>
</div>
