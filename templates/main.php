<?php
script('tagssearch', 'app');
style('tagssearch', 'style');
?>

<div id="app-tagssearch">
    <div class="app-content-wrapper">
        <div class="search-container">
            <h2><?php p($l->t('Buscar Arquivos por Tags')); ?></h2>
            
            <div class="search-box">
                <input type="text" 
                       id="tag-search-input" 
                       placeholder="<?php p($l->t('Digite tags para buscar...')); ?>"
                       autocomplete="off">
                
                <div id="tag-suggestions" class="tag-suggestions hidden"></div>
            </div>

            <div class="selected-tags" id="selected-tags"></div>

            <button id="search-button" class="primary">
                <?php p($l->t('Buscar')); ?>
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