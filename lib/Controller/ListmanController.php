<?php

namespace OCA\Listman\Controller;

use OCA\Listman\AppInfo\Application;
use OCA\Listman\Service\ListmanService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\Util;

class ListmanController extends Controller {
	/** @var ListmanService */
	private $service;

	/** @var string */
	private $userId;

	/** @var IURLGenerator */
	protected $urlGenerator;

	use Errors;

	public function __construct(IRequest $request,
								ListmanService $service,
							  IURLGenerator $urlGenerator,
								$userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->service = $service;
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
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
	public function create(string $title, string $desc, string $redir): DataResponse {
		return new DataResponse($this->service->create($title, $desc, $redir, 
			$this->userId));
	}

	/**
	 * @NoAdminRequired
	 */
	public function update(int $id, string $title,
						   string $desc, string $redir): DataResponse {
		return $this->handleNotFound(function () use ($id, $title, $desc,$redir) {
			return $this->service->update($id, $title, $desc, $redir, $this->userId);
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
   * Want all the members and all the messages too
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function listdetails(string $lid): DataResponse {
		return $this->handleNotFound(function () use ($lid) {
			return $this->service->listdetails(intval($lid), $this->userId);
		});
	}

	/**
   * Want to mark that a message should be sent to everyone
   * currently on the list who hasn't already had it.
	 * @NoAdminRequired
	 */
	public function messagesend(string $mid): DataResponse {
		return $this->handleNotFound(function () use ($mid) {
			return $this->service->messagesend(intval($mid), $this->userId);
		});
	}

	/**
   * Want to fetch how many users have been sent a message,
   * and how many are still in the queue.
	 * @NoAdminRequired
	 */
	public function messagesent(string $mid): DataResponse {
		return $this->handleNotFound(function () use ($mid) {
			return $this->service->messagesent(intval($mid), $this->userId);
		});
	}

	/**
   * Web view of the message.
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function messageview(string $mid) {
		Util::addStyle($this->appName, 'pub');
		$message = 	$this->service->getMessageEntity(intval($mid));
		$list = $this->service->getListEntity(intval($message->getListId()),"");
    $subscribe = $this->urlGenerator->linkToRouteAbsolute('listman.listman.subscribe', ['lid'=>$list->getRandid()]);

    $response = new PublicTemplateResponse($this->appName, 'view', ['list'=>$list,'message'=>$message,'subscribe'=>$subscribe]);
    $response->setHeaderTitle('Email sent');
    $response->setHeaderDetails('To all subscribers');
    $response->setHeaderActions([
        new SimpleMenuAction($subscribe, 'subscribe', 'icon-css-class1', $subscribe, 0),
    ]);
    return $response;
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
	public function subscribePost(string $lid): Response {
		return $this->subscribe($lid);
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
