<?php
script('search_by_tags', 'result');
style('files', 'merged'); // Usa o estilo do app files
style('search_by_tags', 'style');
?>

<div class="app-content">
    <div class="sidebar">
        <h2>Busca por Tags</h2>
        <input id="tag-input" type="text" placeholder="Digite as tags" list="tag-suggestions" class="search"/>
        <datalist id="tag-suggestions"></datalist>
    </div>

    <div class="app-content-list" id="tag-results">
        <!-- Os itens de resultado devem ser adicionados aqui via JS -->
    </div>
</div>
