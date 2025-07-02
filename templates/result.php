<?php
script('search_by_tags', 'result');
style('files', 'merged'); // Usa o estilo do app files
style('search_by_tags', 'style');
?>

<div class="search-by-tags-wrapper">
	<div class="sidebar">
		<h2>Busca por Tags</h2>
		<input id="tag-input" type="text" placeholder="Digite as tags" list="tag-suggestions" class="search"/>
		<datalist id="tag-suggestions"></datalist>
		<div id="tag-folders" class="tag-folders">
		<!-- As pastas das tags serão inseridas aqui -->
		</div>
	</div>

	<div class="app-content-list" id="tag-results">
		<!-- Resultados aqui -->
	</div>
</div>
