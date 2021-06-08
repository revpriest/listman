<?php

namespace OCA\Listman\Service;

use Exception;

use OCA\Listman\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IURLGenerator;
use OCP\Mail\IMailer;
use OCP\L10N\IFactory;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;

use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;
use OCA\Listman\Db\Member;
use OCA\Listman\Db\Message;
use OCA\Listman\Db\MemberMapper;
use OCA\Listman\Db\MessageMapper;

class ListmanService {

	/** @var MaillistMapper */
	private $mapper;
	/** @var MemberMapper */
	private $memberMapper;
	/** @var MessageMapper */
	private $messageMapper;
	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var IMailer */
	private $mailer;
	/** @var IFactory */
	private $l10nFactory;

	public function __construct(MaillistMapper $mapper, MessageMapper $messageMapper, MemberMapper $memberMapper, IURLGenerator $urlGenerator, IMailer $mailer, IFactory $l10nFactory) {
		$this->mapper = $mapper;
		$this->memberMapper = $memberMapper;
		$this->messageMapper = $messageMapper;
		$this->urlGenerator = $urlGenerator;
		$this->mailer = $mailer;
		$this->l10nFactory = $l10nFactory;
	}

  /**
   * Welcome Message Content.
   */
  private function getConfirmTemplate($member,$list,$act): object {
    $subject = $list->getTitle()." subscription";

		$emailTemplate = $this->mailer->createEMailTemplate("listman.confirm", 
      [
        'name' => $member->getName(),
        'email' => $member->getEmail(),
        'subject' => $subject,
      ]
    );
		$emailTemplate->setSubject($subject);
		$emailTemplate->addHeading($subject);

    $link = $this->getConfirmLink($member,$list,$act);
    $actverb = "subscribe";
    if($act=="unsub"){$actverb = "unsubscribe";}

    $t = "Hi ".$member->getName().",";
		$emailTemplate->addBodyText(htmlspecialchars($t),$t);

    $t = "Someone (hopefully you) asked to $actverb to the email-list \"".$list->getTitle()."\"";
		$emailTemplate->addBodyText(htmlspecialchars($t),$t);

    $t = "If you want that, then you'll have to confirm by clicking this link:";
		$emailTemplate->addBodyText(htmlspecialchars($t),$t);

    $t = "If not you should ignore this email.";
		$emailTemplate->addBodyText(htmlspecialchars($t),$t);

		$emailTemplate->addBodyButton(htmlspecialchars($actverb),$link,$link);
    return $emailTemplate;
  }

  /**
  * Generate a link to a confirmation page
  */
  private function getConfirmLink($member,$list,$act): string{
    $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.confirm', ['lid'=>$list->getRandid()]);
    $params = "?conf=".$member->getConf()."&act=".$act;
		return  $base.$params;
  }

	/**
	* Send an actual email to an actual member!
  * We'll use SMTP details if we have 'em.
	*/
	private function sendEmail($member,$template){
     //No actual emailing stuff yet, so we just send to log
    file_put_contents("data/prelog.txt","Emailing ".$member->getEmail(),FILE_APPEND);

		$message = $this->mailer->createMessage();
		$message->useTemplate($template);
		$message->setFrom(['pre@dalliance.net'=>'Mailing Lister']);
		$message->setTo([$member->getEmail()=>$member->getName()]);
		$this->mailer->send($message);
	}



	/**
	* Create a random ID
	*/
	private function randId($n=8){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
    $id = '';
		$clen=strlen($characters) - 1;
    for ($i = 0; $i < $n; $i++) {
        $id .= $characters[rand(0, $clen)];
    }
    return $id;
	}


  /**
  * in order to be able to plug in different storage backends like files
  * for instance it is a good idea to turn storage related exceptions
  * into service related exceptions so controllers and service users
  * have to deal with only one type of exception
  */
	private function handleException(Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new ListNotFound($e->getMessage());
		} else {
			throw $e;
		}
	}

  /**
  * Find all the lists
  */
	public function findAll(string $userId): array {
		return $this->mapper->findAll($userId);
	}

  /**
  * Find all the members in a list
  */
	public function findAllMembers(int $list_id,string $user_id): array {
		return $this->memberMapper->findMembers($list_id,$user_id);
	}

  /**
  * Find all the messages in a list
  */
	public function findAllMessages(int $list_id,string $user_id): array {
		return $this->messageMapper->findMessages($list_id,$user_id);
	}


  /**
  * Find a particular list
  */
	public function find($id, $userId) {
		try {
			return $this->mapper->find($id, $userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}


  /**
  * Find a particular member
  */
	public function findMember($id,$userId) {
		try {
			return $this->memberMapper->find($id,$userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

  /**
  * Find a particular message
  */
	public function findMessage($id,$userId) {
		try {
			return $this->messageMapper->find($id,$userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

  /**
  * Create a new list
  */
	public function create($title, $desc, $redir, $userId) {
		$randid = $this->randId();
		$list = new Maillist();
		$list->setTitle($title);
		$list->setDesc($desc);
		$list->setRedir($redir);
		$list->setRandid($randid);
		$list->setUserId($userId);
		return $this->mapper->insert($list);
	}
  /**
  * Create a new member
  */
	public function createMember($email, $name, $state, $list_id, $userId) {
		$conf = $this->randId(32);
		$member = new Member();
		$member->setEmail($email);
		$member->setName($name);
		$member->setState($state);
		$member->setListId($list_id);
		$member->setUserId($userId);
		$member->setConf($conf);
		return $this->memberMapper->insert($member);
	}
  /**
  * Create a new message
  */
	public function createMessage($subject, $body, $list_id, $userId) {
		$message = new Message();
		$message->setSubject($subject);
		$message->setBody($body);
		$message->setListId($list_id);
		$message->setUserId($userId);
		return $this->messageMapper->insert($message);
	}

  /**
  * Update existing list
  */
	public function update($id, $title, $desc, $userId) {
		try {
			$list = $this->mapper->find($id, $userId);
			$list->setTitle($title);
			$list->setDesc($desc);
			return $this->mapper->update($list);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
  /**
  * Update existing member
  */
	public function updateMember($id, $email, $name, $state,$list_id,$userId) {
		try {
			$member = $this->memberMapper->find($id,$userId);
			$member->setEmail($email);
			$member->setName($name);
			$member->setState($state);
			$member->setListId($list_id);
			return $this->memberMapper->update($member);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
  /**
  * Update existing message
  */
	public function updateMessage($id, $subject, $body,$list_id,$userId) {
		try {
			$message = $this->messageMapper->find($id,$userId);
			$message->setSubject($subject);
			$message->setBody($body);
			$message->setListId($list_id);
			$message->setUserId($userId);
			return $this->messageMapper->update($message);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

  /**
  * Delete existing list
  */
	public function delete($id, $userId) {
		try {
			$list = $this->mapper->find($id, $userId);
			$this->mapper->delete($list);
			return $list;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
  /**
  * Delete existing member
  */
	public function deleteMember($id,$userId) {
		try {
			$member = $this->memberMapper->find($id,$userId);
			$this->memberMapper->delete($member);
			return $member;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
  /**
  * Delete existing message
  */
	public function deleteMessage($id,$userId) {
		try {
			$message = $this->messageMapper->find($id,$userId);
			$this->messageMapper->delete($member);
			return $message;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

  /*
  * List the members of a list
  * We return the list's info too, mostly so the caller
  * can generate a link with the randid
  */
	public function listmembers(int $lid, string $userId): array {
    $list = null;
    try {
      $list = $this->mapper->findAnyones($lid);
		} catch (Exception $e) {
			throw new ListNotFound("Can't find that mailling list");
    }
		return [
      'members'=>$this->memberMapper->findMembers($lid, $userId),
      'list'=>$list
    ];
	}

  /**
  * List the details of a list. Messages and members.
  * We return the list's info too, mostly so the caller
  * can generate a link with the randid
  */
	public function listdetails(int $lid, string $userId): array {
    $list = null;
    try {
      $list = $this->mapper->findAnyones($lid);
		} catch (Exception $e) {
			throw new ListNotFound("Can't find that mailling list");
    }
		return [
      'members'=>$this->memberMapper->findMembers($lid, $userId),
      'messages'=>$this->messageMapper->findMessages($lid, $userId),
      'list'=>$list
    ];
	}

  /**
  * A subscribe action to be used from forms from
  * other sites. We will require a confirmation email.
  * whatever status we are switching to.
  */
  public function subscribe(string $lrid): object {
    $name  = "John";  if(isset($_POST['name'] )){$name  = $_POST['name'] ;}
    $email = "a@b.c"; if(isset($_POST['email'])){$email = $_POST['email'];}
    $conf  = "xxxx";  if(isset($_POST['conf'] )){$conf  = $_POST['conf'] ;}
    $act   = "sub" ;  if(isset($_POST['act']  )){$act   = $_POST['act']  ;}
    $redir = null;    if(isset($_POST['redir'])){$redir = $_POST['redir'];}

		if($redir == "{{Your Return URL}}"){		//They didn't bother to fill it in.
			$redir=null;
		}

    //We have to find the list so we know which Nextcloud user created it.
    $list = null;
    try {
      $list = $this->mapper->findByRandId($lrid);
      $lid = $list->getId();
		} catch (Exception $e) {
			return new TemplateResponse( Application::APP_ID, 'notfound',["message"=>"Bad List ID"],"guest");
    }

    $member = null;
    $new = false;
    $existed = "no";
		try {
			$member = $this->memberMapper->findMemberByEmail($lid,$email);
      $existed = "yes";
		} catch (Exception $e) {
      //Doesn't yet exist so just create it
      $member = new Member();
      $member->setEmail($email);
      $member->setName($name);
      $state = 0;
      $member->setState($state);       #Unconfirmed
      $member->setListId($lid);
      $member->setConf($this->randId(32));
      $member->setUserId($list->getUserId());
		  $member = $this->memberMapper->insert($member);
		}

    $content = $this->getConfirmTemplate($member,$list,$act);
    $this->sendEmail($member,$content);

    if($redir!=null){
		  return new RedirectResponse($redir);
    }

		return new TemplateResponse(
			Application::APP_ID, 'subscribe', [
        'email'=>$email,
        'name'=>$name,
        'act'=>$act,
        'list'=>$list==null?"No List":$list->getTitle(),
      ], 'guest'
		);
  }

  /**
  * A confirm action which should only be valid if
  * it has a confirm parameter and an action.
  */
  public function confirm(string $lrid,string $conf, string $act): object {
    $list = null;
    try {
      $list = $this->mapper->findByRandId($lrid);
		} catch (Exception $e) {
			return new TemplateResponse( Application::APP_ID, 'notfound',["message"=>"Bad List ID"], "guest");
    }

    $member = null;
    try {
      $member = $this->memberMapper->findByConf($conf);
		} catch (Exception $e) {
			return new TemplateResponse( Application::APP_ID, 'notfound',["message"=>"No Member"], "guest");
    }

		if($act=="sub"){
			$member->setState(1);
			$this->memberMapper->update($member);
		}
		if($act=="unsub"){
			$member->setState(-1);
			$this->memberMapper->update($member);
		}

		if(($list->getRedir()!=null)&&($list->getRedir()!="")){
      $url = $list->getRedir();
			$url.= "?membername=".$member->getName();
			$url.= "&memberemail=".$member->getEmail();
			$url.= "&memberstate=".$member->getState();
			$url.= "&listtitle=".$list->getTitle();
			$url.= "&act=".$act;
		  return new RedirectResponse($url);
		}

		return new TemplateResponse( Application::APP_ID, 'confirmed',[
			"member"=>$member,
			"list"=>$list,
			"act"=>$act,
		],"guest");
  }

}



