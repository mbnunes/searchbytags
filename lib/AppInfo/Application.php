<?php
namespace OCA\SearchByTags\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\SearchByTags\Search\TagsSearchProvider;
use OCP\INavigationManager;
use OCP\IL10N;
use OCP\IURLGenerator;

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
        $container = $context->getAppContainer();
        $server = $context->getServerContainer();
        
        // Força o registro da navegação
        $server->getNavigationManager()->add(function () use ($container, $server) {
            $urlGenerator = $server->getURLGenerator();
            $l10n = $container->get(IL10N::class);
            
            return [
                'id' => self::APP_ID,
                'order' => 70,
                'href' => $urlGenerator->getAbsoluteURL('/index.php/apps/search_by_tags/'),
                'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
                'name' => $l10n->t('Search By Tags'),
                'active' => false
            ];
        });
    }
}