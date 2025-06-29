<?php
namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
// use OCP\Util;

class PageController extends Controller {
    
    public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function main(): TemplateResponse {
        return $this->index();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        // Util::addStyle('search_by_tags', 'navigation');
        return new TemplateResponse('search_by_tags', 'main', []);
    }
}