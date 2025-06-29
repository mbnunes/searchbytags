<?php
script('search_by_tags', 'navigation-fix');
script('search_by_tags', 'app');
style('search_by_tags', 'style');
?>
<div id="app-tagssearch">
    <div class="app-content-wrapper">
        <div class="search-container">
            <h2><?php p($l->t('Buscar Arquivos por Tags')); ?></h2>
            
            <div class="search-info">
                <p><?php p($l->t('Use AND para buscar arquivos com TODAS as tags, OR para arquivos com QUALQUER uma das tags')); ?></p>
                <p class="search-example"><?php p($l->t('Exemplos: "2000 AND 2002", "importante OR urgente", "2000 AND 2002 OR 2003"')); ?></p>
            </div>
            
            <div class="search-box">
                <input type="text" 
                       id="tag-search-input" 
                       placeholder="<?php p($l->t('Ex: tag1 AND tag2 OR tag3')); ?>"
                       autocomplete="off">
                
                <div id="tag-suggestions" class="tag-suggestions hidden"></div>
            </div>

            <div class="search-preview" id="search-preview"></div>

            <button id="search-button" class="primary">
                <span class="icon-search"></span>
                <?php p($l->t('Buscar')); ?>
            </button>
            
            <button id="clear-button" class="button">
                <?php p($l->t('Limpar')); ?>
            </button>
        </div>

        <div class="results-container">
            <div id="loading" class="hidden">
                <div class="icon-loading"></div>
            </div>
            
            <div id="search-results"></div>
        </div>
    </div>
</div>