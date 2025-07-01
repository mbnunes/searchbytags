<?php
script('search_by_tags', 'result');
style('files', 'merged'); // Importa o estilo base do app files
style('search_by_tags', 'style'); // Seu CSS customizado
\OCP\Util::addScript('viewer', 'viewer-main'); // Habilita OCA.Viewer
?>

<div class="app-content">
	<div class="app-navigation">
		<ul>
			<li class="app-navigation-entry">
				<strong>Busca por Tags</strong>
			</li>
			<li class="app-navigation-entry">
				<input id="tag-input" type="text" placeholder="Digite as tags" list="tag-suggestions" class="search"/>
				<datalist id="tag-suggestions"></datalist>
			</li>
		</ul>
	</div>
	<div class="app-content-vue">
		<div class="app-content-list">
			<ul id="tag-results" class="files-list"></ul>
		</div>
	</div>
</div>
