<?php

namespace OCA\Listman\Controller;

use OCA\Listman\AppInfo\Application;
use OCA\Listman\Service\ListmanService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class ListmanController extends Controller {
	/** @var ListmanService */
	private $service;

	/** @var string */
	private $userId;

	use Errors;

	public function __construct(IRequest $request,
								ListmanService $service,
								$userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->service = $service;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(): DataResponse {
		return new DataResponse($this->service->findAll($this->userId));
	}

	/**
	 * @NoAdminRequired
	 */
	public function show(int $id): DataResponse {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->find($id, $this->userId);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function create(string $title, string $desc): DataResponse {
		return new DataResponse($this->service->create($title, $desc,
			$this->userId));
	}

	/**
	 * @NoAdminRequired
	 */
	public function update(int $id, string $title,
						   string $desc): DataResponse {
		return $this->handleNotFound(function () use ($id, $title, $desc) {
			return $this->service->update($id, $title, $desc, $this->userId);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function destroy(int $id): DataResponse {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->delete($id, $this->userId);
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function listmembers(string $lid): DataResponse {
		return $this->handleNotFound(function () use ($lid) {
			return $this->service->listmembers(intval($lid), $this->userId);
		});
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function confirm(string $lid): Response {
    $conf = "c";
    $act = "a";
    if(isset($_REQUEST["conf"])){$conf = $_REQUEST['conf'];}
    if(isset($_REQUEST["act"])){$act = $_REQUEST['act'];}
		return $this->service->confirm($lid,$conf,$act);
  }

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function subscribe(string $lid): Response {
		$response = $this->service->subscribe($lid);
    return $response;
	}


}
