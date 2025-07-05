<?php
// script('search_by_tags', 'result');
style('files', 'merged'); // Usa o estilo do app files
style('search_by_tags', 'style');
?>

<div class="search-by-tags-wrapper">
	<div class="sidebar">
		<h2>Busca por Tags</h2>
		<input type="text" id="tag-input" list="tag-suggestions" placeholder="Digite uma tag..." />
<datalist id="tag-suggestions"></datalist>
		<div id="tag-folders" class="tag-folders">
		<!-- As pastas das tags serão inseridas aqui -->
		</div>
	</div>
	<div class="pagination-controls">
		<label for="itemsPerPage">Itens por página:</label>
		<select id="itemsPerPage">
			<option value="12">12</option>
			<option value="24">24</option>
			<option value="48">48</option>
		</select>
	</div>

<div id="pagination-buttons" class="pagination-buttons"></div>

	<div class="app-content-list" id="tag-results">
		<!-- Resultados aqui -->
	</div>
</div>
