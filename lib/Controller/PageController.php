<?php
namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
    public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     */
    public function index(): TemplateResponse {
        return new TemplateResponse('search_by_tags', 'main', []);
    }

    /**
     * @NoAdminRequired
     */
    public function viewResult(): TemplateResponse {
        return new TemplateResponse('search_by_tags', 'result', []);
    }
}
