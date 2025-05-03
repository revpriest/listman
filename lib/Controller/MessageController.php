<?php

namespace OCA\Listman\Controller;

use OCA\Listman\AppInfo\Application;
use OCA\Listman\Service\ListmanService;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class MessageController extends OCSController {
	/** @var ListmanService */
	private $service;
	private $userId;

	use Errors;

	public function __construct(IRequest $request,
								ListmanService $service,
								string $userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->service = $service;
		$this->userId = $userId;
	}

	#[NoAdminRequired]
	public function index(): DataResponse {
		return new DataResponse($this->service->findAllMessages($this->userId));
	}

	#[NoAdminRequired]
	public function show(int $id): DataResponse {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->findMessage($id, $this->userId);
		});
	}

	#[NoAdminRequired]
	public function create(?string $subject, ?string $body, int $list_id): DataResponse {
		return new DataResponse($this->service->createMessage($subject, $body, $list_id, $this->userId));
	}

	#[NoAdminRequired]
	public function update(int $id, ?string $subject, ?string $body, int $list_id): DataResponse {
		return $this->handleNotFound(function () use ($id, $subject, $body, $list_id) {
			return $this->service->updateMessage($id, $subject, $body, $list_id, $this->userId);
		});
	}

	#[NoAdminRequired]
	public function destroy(int $id): DataResponse {  
		return $this->handleNotFound(function () use ($id) {
			return $this->service->deleteMessage($id, $this->userId);
		});
	}
}
