<?php

namespace OCA\SearchByTags\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Search\ISearchProvider;
use OCA\SearchByTags\Search\SearchProvider;
class Application extends App implements IBootstrap {
    public function __construct(array $urlParams = []) {
        parent::__construct('search_by_tags', $urlParams);
    }

   public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(SearchProvider::class);
	}

    public function boot(IBootContext $context): void {
        // Aqui você pode executar lógica de inicialização
    }
}
