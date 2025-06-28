use OCP\ITagManager;
use OCP\IUserSession;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class SearchByTagsController extends Controller {
    private ITagManager $tagManager;
    private IUserSession $userSession;
    private IRootFolder $rootFolder;

    public function __construct(
        $appName,
        IRequest $request,
        ITagManager $tagManager,
        IUserSession $userSession,
        IRootFolder $rootFolder
    ) {
        parent::__construct($appName, $request);
        $this->tagManager = $tagManager;
        $this->userSession = $userSession;
        $this->rootFolder = $rootFolder;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function search(): DataResponse {
        $user = $this->userSession->getUser();
        $folder = $this->rootFolder->getUserFolder($user->getUID());

        $queryTags = $this->request->getParam('tags');
        $tagNames = array_filter(array_map('trim', explode(',', $queryTags)));

        if (empty($tagNames)) {
            return new DataResponse(['error' => 'No tags provided'], 400);
        }

        $filesWithTags = [];

        foreach ($tagNames as $tagName) {
            $tag = $this->tagManager->getTag($user, $tagName, 'files');
            if ($tag === null) {
                return new DataResponse(["error" => "Tag '$tagName' not found"], 404);
            }

            $fileIds = $this->tagManager->getIdsForTag($tag);
            $filesWithTags[] = $fileIds;
        }

        // Interseção entre os arrays de file IDs
        $matchingFileIds = array_shift($filesWithTags);
        foreach ($filesWithTags as $tagFileIds) {
            $matchingFileIds = array_intersect($matchingFileIds, $tagFileIds);
        }

        // Mapear IDs para caminhos
        $results = [];
        foreach ($matchingFileIds as $fileId) {
            $file = $folder->getById($fileId);
            if ($file) {
                $results[] = $file[0]->getPath();
            }
        }

        return new DataResponse(['results' => $results]);
    }
}
