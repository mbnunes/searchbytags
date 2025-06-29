<?php
namespace OCA\SearchByTags\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\SearchByTags\Search\TagsSearchProvider;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

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
        $server = $context->getServerContainer();
        
        // Registra a navegação programaticamente
        $navigationManager = $server->get(INavigationManager::class);
        $urlGenerator = $server->get(IURLGenerator::class);
        $userSession = $server->get(IUserSession::class);
        
        if ($userSession->isLoggedIn()) {
            $navigationManager->add(function () use ($urlGenerator) {
                return [
                    'id' => self::APP_ID,
                    'order' => 15,
                    'href' => $urlGenerator->linkToRoute('search_by_tags.page.index'),
                    'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
                    'name' => 'Search By Tags',
                ];
            });
        }
    }
}