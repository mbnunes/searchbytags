<?php
script('search_by_tags', 'result');
style('files', 'merged'); // Usa o estilo do app files
?>

<div class="section">
    <h2>Busca por Tags</h2>
    <input id="tag-input" type="text" placeholder="Digite as tags (ex: 2000 AND 2002)" list="tag-suggestions" class="search" style="width: 100%; padding: 0.5em; font-size: 1em;"/>
    <datalist id="tag-suggestions"></datalist>

    <ul id="tag-results" class="filelist" style="margin-top: 1em;"></ul>
</div>
