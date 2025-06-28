namespace OCA\SearchByTags\Search;

use OCP\Search\IProvider;
use OCP\Search\Result;
use OCP\Files\IRootFolder;
use OCP\ITagManager;

class SearchProvider implements IProvider {
	private IRootFolder $rootFolder;
	private ITagManager $tagManager;

	public function __construct(IRootFolder $rootFolder, ITagManager $tagManager) {
		$this->rootFolder = $rootFolder;
		$this->tagManager = $tagManager;
	}

	public function getId(): string {
		return 'search_by_tags';
	}

	public function getName(): string {
		return 'Search by Tags';
	}

	public function search(string $query): array {
		$tags = array_map('trim', explode(',', $query));
		if (count($tags) === 0) {
			return [];
		}

		$user = \OC::$server->getUserSession()->getUser();
		$files = $this->rootFolder->getUserFolder($user->getUID())->getDirectoryListing();

		$results = [];
		foreach ($files as $file) {
			if ($file->getType() !== 'file') {
				continue;
			}

			$fileTags = $this->tagManager->getTagsForObjects([$file->getId()], 'files')['files'][$file->getId()] ?? [];

			$tagNames = array_map(fn($tag) => $tag->getName(), $fileTags);

			if (count(array_intersect($tags, $tagNames)) === count($tags)) {
				$results[] = new Result(
					$file->getName(),
					$file->getPath(),
					$this->getId(),
					'/apps/files?dir=' . urlencode(dirname($file->getPath())),
					'icon'
				);
			}
		}

		return $results;
	}
}
