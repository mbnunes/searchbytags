<?php
namespace OCA\TagsSearch\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\SearchByTags\Search\TagsSearchProvider;

class Application extends App implements IBootstrap {
    public const APP_ID = 'search_by_tags';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // Registra o provedor de busca
        $context->registerSearchProvider(TagsSearchProvider::class);
    }

    public function boot(IBootContext $context): void {
        // Inicialização do app
    }
}