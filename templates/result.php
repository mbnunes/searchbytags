<script src="<?php print_unescaped(\OC::$server->getURLGenerator()->linkTo('search_by_tags', 'js/result.js')); ?>"></script>

<div id="search-results">
  <h2>Resultados da busca por tags</h2>
  <div class="file-grid" style="display: flex; flex-wrap: wrap; gap: 20px;"></div>
</div>

<style>
.file-card {
  width: 150px;
  text-align: center;
  font-size: 14px;
}
.file-thumb {
  width: 100px;
  height: 100px;
  object-fit: cover;
  margin-bottom: 5px;
}
</style>
