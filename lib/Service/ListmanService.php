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
use OCP\AppFramework\Http\Template\PublicTemplateResponse;

use \OCP\BackgroundJob\Job;
use \OCP\BackgroundJob\IJobList;

use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;
use OCA\Listman\Db\Member;
use OCA\Listman\Db\Message;
use OCA\Listman\Db\Sendjob;
use OCA\Listman\Db\React;
use OCA\Listman\Db\MemberMapper;
use OCA\Listman\Db\MessageMapper;
use OCA\Listman\Db\SendjobMapper;
use OCA\Listman\Db\ReactMapper;
use OCA\Listman\Cron\ListmanSend;

class ListmanService {

	/** @var MaillistMapper */
	private $mapper;
	/** @var MemberMapper */
	private $memberMapper;
	/** @var MessageMapper */
	private $messageMapper;
	/** @var SendjobMapper */
	private $sendjobMapper;
	/** @var ReactMapper */
	private $reactMapper;
	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var IMailer */
	private $mailer;
  /** @var IJobList **/
  private $jobList;
	/** @var IFactory */
	private $l10nFactory;


	public function __construct(MaillistMapper $mapper, MessageMapper $messageMapper, MemberMapper $memberMapper, ReactMapper $reactMapper,  SendjobMapper $sendjobMapper, IURLGenerator $urlGenerator, IMailer $mailer, IFactory $l10nFactory, IJobList  $jobList) {
		$this->mapper = $mapper;
		$this->memberMapper = $memberMapper;
		$this->messageMapper = $messageMapper;
		$this->sendjobMapper = $sendjobMapper;
		$this->reactMapper = $reactMapper;
		$this->urlGenerator = $urlGenerator;
		$this->mailer = $mailer;
		$this->jobList = $jobList;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	* Get the reacts to a message
	*/
	public function getReactsForMessage($message_id){
		return $this->reactMapper->findAllForMessage($message_id);
	}

	/**
	* The buttons that go on a message
	*/
	public function getEmailButtons($message,$list){
    $subscribe = $this->getLink("subscribe",$list->getRandid());
    $share = $this->getLink("view",$message->getId());
    $reply = "mailto:pre@dalliance.net";
    $html = "";
		$html.="<p class=\"ar\">Anonymous React:</p>";
		$html.="<ul class=\"ebtns\">";
		$html.="<li><a href=\"$share?r=❤\" class=\"ebtn\">❤</a></li>";
		$html.="<li><a href=\"$share?r=👍\" class=\"ebtn\">👍</a></li>";
		$html.="<li><a href=\"$share?r=👎\" class=\"ebtn\">👎</a></li>";
		$html.="<li><a href=\"$share?r=🤣\" class=\"ebtn\">🤣</a></li>";
		$html.="<li><a href=\"$share?r=😢\" class=\"ebtn\">😢</a></li>";
		$html.="<li><a href=\"$share?r=😮\" class=\"ebtn\">😮</a></li>";
		$html.="</ul>";
		$html.="<ul class=\"btns\">";
		$html.="<li><a href=\"$subscribe\" class=\"btn\">Un/Subscribe</a></li>";
		$html.="<li><a href=\"$share\" class=\"btn\">Share</a></li>";
		$html.="<li><a href=\"$reply\" class=\"btn\">Chat</a></li>";
		$html.="</ul>";

		$plain.="---\n";
    $plain.="  * Un/Subscribe: $subscribe\n";
		$plain.=" * Share: $share\n";
		$plain.=" * Chat: $reply\n";
		$plain.="---\n\n";
		return ['html'=>$html,'plain'=>$plain]; 
  }

	/**
	* A Style-sheet for the emails
	*/
	public function getEmailStylesheet(){
		$ret = "<style>
h1, h2, h3, h4, h5, h6{
  text-align:left;
  margin:0.01em;
  padding: 0.01em;
  font-weight: normal;
  margin-bottom: 0.1em;
  font-size: 1.0em;
}
h1{
  font-size: 2em;
}
h2{
  font-size: 1.6em;
}
h3{
  font-size: 1.4em;
}
h4{
  font-size: 1.2em;
}
h5{
  font-size: 1.1em;
}
p{
	margin-bottom: 1em;
}
.inlineimg{
  width: 20em;
  max-width: 100%;
  padding: 0px;
  margin: 0px;
	display: inline-block;
	box-shadow: 0em 0em 0.4em rgba(.0,.0,.0,.8);
}
.ebtns,
.btns{
	display: block;
  text-align: center;
	list-style-type: none;
	margin: auto;
  padding: 0.1em;
}
.ebtns li,
.btns li{
	float left;
	display: inline-block;
	margin: 0px;
	padding: 2px;
  margin-bottom: 0.5em;
  line-height: 3.3em;
}
.ebtns li{
  font-size: 2em;
  line-height: 1.3em;
}
.btn{
	background: rgb(28,24,96);
	background: linear-gradient(171deg, rgba(28,24,96,1) 0%, rgba(20,41,69,1) 8%, rgba(41,77,125,1) 23%, rgba(33,49,116,1) 52%, rgba(22,28,88,1) 100%);
	border: 2px solid black;
	border-radius: 1.3em;
	font-size: 1.5em;
	color: #fc3;
	text-decoration: none;
	padding: 0.3em 1.2em;
	box-shadow: 2px 2px 0.3em rgba(.0,.0,.0,.8);
	text-shadow: 2px 2px 0.2em rgba(.0,.0,.0,.8);
}
.ebtn{
	background: rgb(28,24,96);
	background: linear-gradient(171deg, rgba(28,24,96,1) 0%, rgba(20,41,69,1) 8%, rgba(41,77,125,1) 23%, rgba(33,49,116,1) 52%, rgba(22,28,88,1) 100%);
	text-decoration: none;
	margin: 0.1px 0.1em;
	padding: 0.1px 0.2em;
	border: 2px solid black;
	border-radius: 1em;
	text-decoration: none;
	box-shadow: 2px 2px 0.3em rgba(.0,.0,.0,.8);
	text-shadow: 2px 2px 0.2em rgba(.0,.0,.0,.8);
}
.ar{
  text-align: center;
  padding: 0.3em;
  margin: 0.1em;
  font-size: 0.8em;
}
.ebtn:hover,
.btn:hover{
	background: rgb(28,96,24);
	background: linear-gradient(171deg, rgba(28,96,24,1) 0%, rgba(20,69,41,1) 8%, rgba(41,125,77,1) 23%, rgba(33,116,49,1) 52%, rgba(22,88,28,1) 100%);
  color: black;
}
.ebtn:active,
.btn:active{
	background: rgb(96,28,24);
	background: linear-gradient(171deg, rgba(96,28,24,1) 0%, rgba(69,20,41,1) 8%, rgba(125,41,77,1) 23%, rgba(116,33,49,1) 52%, rgba(88,22,28,1) 100%);
  color: black;
}
</style>";
		return $ret;
	}


	/**
	* Convert a message into plain and HTML,
	* interpreting the link commands and stuff
	*/
  function messageBodyToPlainAndHtml($message){
		$bhtml = "<p>";
		$bplain = "";
    $body = $message->getBody();
		$lines = explode("\n",$body);

		foreach($lines as $p){
			if($p==""){
			  $bhtml.="</p><p>";
			  $bplain.="\n\n";
			}else{
				$params = [""];
				if($p[0]=="/"){
					$params = explode(" ",$p);
				}
				switch($params[0]){
						case "/h1":
						case "/h2":
						case "/h3":
						case "/h4":
							$cmd = array_shift($params);
							$num = $cmd[2];
							$dat = implode(" ",$params);
							$dat_h = htmlspecialchars($dat);
							if($dat!=""){
								$bhtml.="</p><h$num>$dat_h</h$num>\n<p>";
								$indent = "";
								for($n=0;$n<intval($num);$n++){
									$indent.="#";
								}
								$bplain.="\n$indent $dat\n\n";
							}
							break;

						case "/img":
							$cmd = array_shift($params);
							$img = array_shift($params);
							$alt = implode(" ",$params);
							$alt_h = htmlspecialchars($alt);
							$bhtml.="</p><a href=\"$img\"><img class=\"inlineimg\" alt=\"$alt\" title=\"$alt\" src=\"$img\"></img></a>\n<p>";
							$bplain.="\n * $img ($alt)\n";
							break;

						case "/link":
							$cmd = array_shift($params);
							$lnk = array_shift($params);
							$dsc = implode(" ",$params);
							if($dsc==""){$dsc = "Link";}
							$dsc_h = htmlspecialchars($dsc);
							$bhtml.=" <a href=\"$lnk\" class=\"inlinelnk\">$dsc_h</a> ";
							$bplain.=" ($dsc)[ $lnk ]";
							break;

						default:
							$bhtml.=htmlspecialchars($p);
							$bplain.=$p;
							break;
				}
			}
		}
		$bhtml.= "</p>";
		return ["html"=>$bhtml,"plain"=>$bplain];
	}

  /**
   * Send Message Template
   */
  private function getMessageTemplate($member,$message,$list): object {
    $subject = $message->getSubject();

		$emailTemplate = $this->mailer->createMessage(); 
		$emailTemplate->setSubject($subject." [".$list->getTitle()."]");
		$emailTemplate->setFrom(["pre@dalliance.net"=>"Pre fixthis"]);
		$emailTemplate->setTo([$member->getEmail()=>$member->getname()]);

		$html=$this->getEmailStylesheet();; 
    $plain="";

		$html.='<div class="messageText">';
		$html.="<h1>".$subject."</h1>";
		$html.="<h2>From:".$list->getTitle()."</h2>";
		$html.="<h2>To: All Subscribers</h2>";
		$html.="<h2>Date: ".$message->getCreatedAt()."</h2>";
		$html.="<hr/>";

		$plain.="# $subject\n";
		$plain.="## From: ".$list->getTitle()."\n";
		$plain.="## To: All Subscribers\n";
		$plain.="## Date:x".$message->getCreatedAt()."x\n";
		$plain.="---\n";

		//The Message!
		$both = $this->messageBodyToPlainAndHtml($message);
		$bhtml = $both['html'];
		$bplain= $both['plain'];

		$html.=$bhtml;
		$plain.=$bplain;

    //Three calls to action at the bottom of each mail.
    //Subscribe/Unsubscribe - Public Link remember, shareable. 

		$both = $this->getEmailButtons($message,$list);
		$html.=$both['html'];
		$plain.=$both['plain'];

		$emailTemplate->setPlainBody($plain);
		$emailTemplate->setHtmlBody($html);

    return $emailTemplate;
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
  * Generate a link to a confirmation page.
  * THESE SHOULD NOT BE INCLUDED IN A GENERAL MESSAGE
  * These are SECRET CODE links
  */
  private function getConfirmLink($member,$list,$act): string{
    $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.confirm', ['lid'=>$list->getRandid()]);
    $params = "?conf=".$member->getConf()."&act=".$act;
		return  $base.$params;
  }

  /**
  * Generate a link to a public page, no identifying information
  * or random-ids.
  */
  private function getLink($page,$param):string {
    switch($page){
      case "subscribe":
        $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.subscribe', ['lid'=>$param]);
        break;
      case "view":
        $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.messageview', ['mid'=>$param]);
      default:
        break;
    }
		return  $base;
  }

	/**
	* Send an actual email to an actual member!
	*/
	private function sendEmail($member,$template){
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
		$now = new \DateTime();
		$message = new Message();
		$message->setSubject($subject);
		$message->setCreatedAt($now->format("Y-m-d H:i:s"));
		$message->setBody($body);
		$message->setListId($list_id);
		$message->setUserId($userId);
		return $this->messageMapper->insert($message);
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
  * Update existing list
  */
	public function update($id, $title, $desc, $redir, $userId) {
		try {
			$list = $this->mapper->find($id, $userId);
			$list->setTitle($title);
			$list->setDesc($desc);
			$list->setRedir($redir);
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
  * Get the details on how sent a message is.
  * Who has it already? Who is it still to go to?
  */
	public function messagesent(int $mid, string $userId): array {
    return $this->sendjobMapper->getMessageSentData($mid);
  }

  /**
  * Return a message object
  */
	public function getMessageEntity(int $mid): object{
    return $this->messageMapper->find($mid);
  }

  /**
  * Return a List object
  */
	public function getListEntity(int $lid, string $userId): object{
    return $this->mapper->find($lid, $userId);
  }

  /**
  * Send the message to everyone on the list.
  * We just mark it as ready for the cron to send
  * here really.
  */
	public function messagesend(int $mid, string $userId): array {
    $message = null;
    $newlyAdded = 0;
    $alreadyAdded = 0;
    $alreadySent = 0;
    try {
      $message = $this->messageMapper->find($mid);
		} catch (Exception $e) {
			throw new MessageNotFound("Can't find that message");
    }
    try {
      $list = $this->mapper->find($message->getListId(),$userId);
		} catch (Exception $e) {
			throw new ListNotFound("Can't find the list that message was made for");
    }
    try {
      $members = $this->memberMapper->findMembers($list->getId(),$userId);
		} catch (Exception $e) {
			throw new ListNotFound("Can't find the list that message was made for");
    }
    foreach($members as $member){
      try{
        $sendJob = $this->sendjobMapper->find($message->getId(),$member->getId());
        if($sendJob->getState()==1){
          $alreadySent+=1;
        }
        $alreadyAdded+=1;
		  } catch (Exception $e) {
        $newlyAdded+=1;
        file_put_contents("data/prelog.txt","Adding Member ".$member->getId()."\n",FILE_APPEND);
        $sendJob = new Sendjob();
        $sendJob->setMemberId($member->getId());
        $sendJob->setMessageId($message->getId());
        $sendJob->setState(0);
        $this->sendjobMapper->insert($sendJob);
      }
    }
	  file_put_contents("data/prelog.txt","Starting Cron ".microtime(true)."\n",FILE_APPEND);
    $this->jobList->remove(ListmanSend::class);
    $this->jobList->add(ListmanSend::class);
	  file_put_contents("data/prelog.txt","Started Cron ".microtime(true)."\n",FILE_APPEND);
		return [
      'message'=>$message,
      'new'=>$newlyAdded,
      'old'=>$alreadyAdded,
      'sent'=>$alreadySent,
      'total'=>$newlyAdded+$alreadyAdded,
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

  public function formValid($email,$name){
      if($email==null) return false;
      if($email=="") return false;
      if($name=="") return false;
      if($name==null) return false;
      //todo: At least check for an @ ?
      return true;
  }

  /**
  * A subscribe action to be used from forms from
  * other sites. We will require a confirmation email.
  * whatever status we are switching to.
  */
  public function subscribe(string $lrid): object {
    $name  = "";  if(isset($_POST['name'] )){$name  = $_POST['name'] ;}
    $email = ""; if(isset($_POST['email'])){$email = $_POST['email'];}
    $conf  = "";  if(isset($_POST['conf'] )){$conf  = $_POST['conf'] ;}
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
      $t = $list->getRedir();
      if(($t!=null)&&($t!="")){
        $redir = $t;
      }
		} catch (Exception $e) {
      $response = new PublicTemplateResponse(Application::APP_ID, 'notfound', ['messge'=>"Can't find list"]);
		  \OCP\Util::addStyle(Application::APP_ID, 'pub');
      $response->setHeaderTitle('Not Found');
      $response->setHeaderDetails('Dunno what that list is');
      return $response;
    }

    $member = null;
    $new = false;
    $existed = "no";
		try {
			$member = $this->memberMapper->findMemberByEmail($lid,$email);
      $existed = "yes";
		} catch (Exception $e) {
      //Doesn't yet exist so just create it
      if($this->formValid($email,$name)){
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
		}

    if($this->formValid($email,$name)){
      $content = $this->getConfirmTemplate($member,$list,$act);
      $this->sendEmail($member,$content);

      //All good, so we redirect to owner's URL?
      if($redir!=null){
        return new RedirectResponse($redir);
      }

      //Thanks, that's it.
      $response = new PublicTemplateResponse(Application::APP_ID, 'thanks', [
        'email'=>$email,
        'name'=>$name,
        'act'=>$act,
        'url'=>$act,
        'redir'=>$redir,
        'list'=>$list,
      ]);
      $response->setHeaderTitle('Thanks');
      $response->setHeaderDetails('Aw, its like you care!');
		  \OCP\Util::addStyle(Application::APP_ID, 'pub');
      return $response;
    }

    //Show the subscribe form
    $response = new PublicTemplateResponse(Application::APP_ID, 'subscribe', [
      'email'=>$email,
      'name'=>$name,
      'act'=>$act,
      'url'=>$url,
      'redir'=>$redir,
      'list'=>$list,
    ]);
    $response->setHeaderTitle($list->getTitle().' - subscribe');
    $response->setHeaderDetails('Tell me your details.');
		\OCP\Util::addStyle(Application::APP_ID, 'pub');
    return $response;
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

    //Show the confirmation page
    $response = new PublicTemplateResponse(Application::APP_ID, 'confirmed', [
      'member'=>$member,
      'list'=>$list,
      'act'=>$act,
      'redir'=>$redir,
    ]);
    $response->setHeaderTitle($list->getTitle().' - confirmed');
    $response->setHeaderDetails('Thanks, the '.$act.' is conformed.');
		\OCP\Util::addStyle(Application::APP_ID, 'pub');
    return $response;
  }



	/**
	* Do a single send-job. We send a particular email to
	* a particular user as defiend by a sendjob object
  *
  * We return the new state, so:
  * 0 - Job not done, should still be attempted
  * 1 - Job complete
  * < 0 - Errors
	*/
	public function sendEmailToMember($sendJob){
		$member = $this->memberMapper->find($sendJob->getMemberId());
		$message = $this->messageMapper->find($sendJob->getMessageId());
		$list = $this->mapper->find($message->getListId(),"");

    file_put_contents("/var/www-nextcloud/data/prelog.txt","Emailing ".$member->getEmail()." with message ".$message->getSubject()."\n",FILE_APPEND);
     
    $email = $this->getMessageTemplate($member,$message,$list);
		$this->mailer->send($email);

    file_put_contents("/var/www-nextcloud/data/prelog.txt","Emailed ".$member->getEmail()." with message ".$message->getSubject()."\n",FILE_APPEND);
		return 1;   
	}

  /**
  * RunCron. Every five minutes or so, when there are messages
  * to send, we should get called here. We return true if there
  * is more work to do, and false to let the caller know the
  * jobs are all done and the cron can be cancelled until further
  * user action.
  */
  public function runCron(){
    $maxToTry= 1;
    $tried = 0;

    while($tried < $maxToTry){
      try{
        $nextToSend = $this->sendjobMapper->getNextToSend();
				$state = $this->sendEmailToMember($nextToSend);
        $nextToSend->setState($state);
			  $this->sendjobMapper->update($nextToSend);
    
      }catch(Exception $e){
        return false;
      }
      $tried++;
    }
    return true;
  }


	/**
	* Register a reaction, which may include a simple
	* page-load
	*/
	public function registerReaction($message,$r){
		//Only valid reactions
    switch($r){
      case "❤":
      case "👍":
      case "👎":
      case "🤣":
      case "😢":
      case "😮":
        break;
      default:
    	  $r = "📃";
		    break;
    }
		try{
		  $react = $this->reactMapper->findByMessageAndSymbol($message->getId(),$r);
		}catch(Exception $e){
			$react = new React();
			$react->setSymbol($r);
			$react->setCount(0);
			$react->setMessageId($message->getId());
		  $react = $this->reactMapper->insert($react);
		}
		$count = $react->getCount();
		$react->setCount($count+1);
	  return $this->reactMapper->update($react);
	}

}



