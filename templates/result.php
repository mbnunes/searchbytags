<?php
script('search_by_tags', 'result');
style('files', 'merged'); // Usa o estilo do app files
style('search_by_tags', 'style');
// Adicione estas linhas para garantir que o Viewer seja carregado

\OC_Util::addScript('viewer', 'viewer');
\OC_Util::addStyle('viewer', 'viewer');
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

	<div class="app-content-list">
		<!-- Container para os controles de paginação -->
		<div class="pagination-controls-wrapper">
			<!-- Os controles serão inseridos aqui via JavaScript -->
		</div>
		
		<div id="tag-results">
			<!-- Resultados aqui -->
		</div>
	</div>
</div>