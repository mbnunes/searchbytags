<?php
script('search_by_tags', 'result');
style('files', 'merged'); // Usa o estilo do app files
style('search_by_tags', 'style'); 
?>

<div class="container">
    <div class="sidebar">
        <h2>Busca por Tags</h2>
        <input id="tag-input" type="text" placeholder="Digite as tags" list="tag-suggestions" class="search"/>
        <datalist id="tag-suggestions"></datalist>
    </div>
    <div class="main-content">
        <ul id="tag-results" class="filelist"></ul>
    </div>
</div>