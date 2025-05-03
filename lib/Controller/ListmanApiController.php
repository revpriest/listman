<?php

namespace OCA\Listman\Controller;

use OCA\Listman\AppInfo\Application;
use OCA\Listman\Service\ListmanService;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ListmanApiController extends OCSController {
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

  #[CORS]
  #[NoCSRFRequired]
  #[NoAdminRequired]
	public function index(): DataResponse {
		return new DataResponse($this->service->findAll($this->userId));
	}

	#[CORS]
	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function show(int $id): DataResponse {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->find($id, $this->userId);
		});
	}

	#[CORS]
	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function create(?string $title, ?string $desc): DataResponse {
		return new DataResponse($this->service->create($title, $desc,
			$this->userId));
	}

	#[CORS]
	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function update(int $id, ?string $title, ?string $desc): DataResponse {
		return $this->handleNotFound(function () use ($id, $title, $desc) {
			return $this->service->update($id, $title, $desc, $this->userId);
		});
	}

	#[CORS]
	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function destroy(int $id): DataResponse {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->delete($id, $this->userId);
		});
	}
}
