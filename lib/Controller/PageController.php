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
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        // Compatibilidade com a rota padrão
        return $this->viewResult();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function viewResult(): TemplateResponse {
        return new TemplateResponse('search_by_tags', 'result', []);
    }
}
