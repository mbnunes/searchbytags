<?php

namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
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
        return $this->viewResult();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function viewResult(): TemplateResponse {
        $query = $this->request->getParam('query', '');

        $response = new TemplateResponse('search_by_tags', 'result', [
            'query' => $query
        ]);

        // Permite scripts inline com nonce gerado pelo Nextcloud
        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);
        $response->setContentSecurityPolicy($csp);

        return $response;
    }
}
