<?php
script('search_by_tags', 'result');
style('files', 'files'); // Estilo do app Files
?>

<div class="section">
    <h2>Busca por Tags</h2>
    
    <input type="text" id="tag-input" placeholder="Digite uma ou mais tags..." style="width: 100%; padding: 0.5em; margin-bottom: 1em; border: 1px solid #ccc; border-radius: 4px;">

    <div id="tag-results" class="files-list-view"></div>
