<?php

namespace OCA\SearchByTags\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class SearchByTagsController extends Controller {
    public function __construct($AppName, IRequest $request) {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function search(): DataResponse {
        return new DataResponse(['message' => 'Rota ativa e funcionando!']);
    }
}
