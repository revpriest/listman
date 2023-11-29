<?php

namespace OCA\Listman\Controller;

use OCP\AppFramework\Http\ContentSecurityPolicy;
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
	public function create(?string $title, ?string $desc, ?string $redir, ?string $fromname, ?string $fromemail, ?string $buttontext, ?string $buttonlink, ?string $footer, ?string $suburl, ?string $shareurl): DataResponse {
		return new DataResponse($this->service->create($title, $desc, $redir, $fromname, $fromemail, $buttontext, $buttonlink, $footer, $suburl, $shareurl, $this->userId));
	}

	/**
	 * @NoAdminRequired
	 */
	public function update(int $id, ?string $title, ?string $desc, ?string $redir, ?string $fromname, ?string $fromemail, ?string $buttontext, ?string $buttonlink, ?string $footer, ?string $suburl, ?string $shareurl): DataResponse {
		return $this->handleNotFound(function () use ($id, $title, $desc,$redir,$fromname,$fromemail,$buttontext,$buttonlink,$footer,$suburl,$shareurl) {
			return $this->service->update($id, $title, $desc, $redir, $fromname,$fromemail,$buttontext,$buttonlink,$footer,$suburl,$shareurl,$this->userId);
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
	public function messagetext(string $rid) {
    return $this->messageview($rid,"plain");
  }

	/**
   * Stats view of the message. What response did it get?
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function messagestat(string $rid) {
    return $this->messageview($rid,"stat");
  }

	/**
   * Just show the HTML of the react-widget for pasting into a copy 
	 * of the message in a blog page
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function messagewidget(string $rid) {
    return $this->messageview($rid,"widget");
  }

	/**
   * Just increment the view-count on a a blank image
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function messageinc(string $rid) {
    return $this->messageview($rid,"increment");
  }

	/**
   * Load/Save settings.
	 */
	public function settings($settings) {
    $post = json_decode(file_get_contents('php://input'),true);
    return $this->service->settings($post);
  }

	/**
   * Web view of the message.
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function messageview(string $rid,$ttype="html") {
		Util::addStyle($this->appName, 'pub');
		Util::addScript($this->appName, 'listman-bonus');
		$message = 	$this->service->getMessageEntityByRandId($rid);
		$list = $this->service->getListEntity(intval($message->getListId()),"");
    $subscribe = $this->urlGenerator->linkToRouteAbsolute('listman.listman.subscribe', ['lid'=>$list->getRandid()]);
    
		if($list->getSuburl()){
      $subscribe = $list->getSuburl();
		}

    $r = "📃";
    if(isset($_REQUEST['r'])){
      $r = $_REQUEST['r'];
    }

		if(($ttype!="widget")&&($ttype!="stat")&&($ttype!="inc")){
      $this->service->registerReaction($message,$r);
		}

    $body = $this->service->messageRender($message,$list);
		$url = $this->service->getShareUrl($message->getRandid()); 
		$reacts = $this->service->getReactsForMessage($message->getId()); 

    if($ttype=="plain"){
      $body['plain'] = "<pre>".$body['plain']."</pre>";
    }

    if($ttype=="widget"){
			//Special copy-pastable version of just the widget, to put into a blog copy or whatever.
		  $buttons = $this->service->getEmailButtons($message,$list); 
      print "<h1 style=\"text-align:center\">Widget Code</h1>";
      print "<p style=\"text-align:center\">Copy this into a HTML page to make it show the react-buttons for this message</p>";
      print "<div style=\"padding: 2em;width: 50%; margin-left: 25%; border: 2px solid black;\">";
			print htmlspecialchars($buttons['html']);
      print "</div>";
      print "<h2 style=\"text-align:center\">Eg</h2>";
			print $buttons['html'];
      exit;
    }

    if($ttype=="increment"){
      $ttype="html";
    }

		if($ttype=="stat"){
			//Special machine-readable view of just the stats on who pressed like etc.
      foreach($reacts as $r){
        print($r->getSymbol().":".$r->getCount()."\n");
			}
		  exit;
		}

    $response = new PublicTemplateResponse($this->appName, 'view', ['list'=>$list,'message'=>$message,'subscribe'=>$subscribe,"url"=>$url,"react"=>$reacts,"body"=>$body[$ttype]]);
    $response->setHeaderTitle($message->getSubject()." [".$list->getTitle()."]");
    $response->setHeaderDetails("To all subscribers at ".$message->getCreatedAt());
    $response->setHeaderActions([
        new SimpleMenuAction($subscribe, 'subscribe', 'icon-css-class1', $subscribe, 0),
    ]);
		$policy = new ContentSecurityPolicy();
    $policy->addAllowedScriptDomain(['\'unsafe-inline\'','\'unsafe-eval\'','\'script-src\'']);
		$policy->addAllowedImageDomain('*');
		// Needed for the ES5 compatible build of PDF.js
		$policy->allowEvalScript(true);
		$response->setContentSecurityPolicy($policy);
    return $response;
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function confirmPost(string $lid): Response {
    return $this->confirm($lid);
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
