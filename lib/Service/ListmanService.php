<?php

namespace OCA\Listman\Service;

use Exception;

use OCA\Listman\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

use \OCP\BackgroundJob\Job;
use \OCP\BackgroundJob\IJobList;

use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;
use OCA\Listman\Db\Settings;
use OCA\Listman\Db\SettingsMapper;
use OCA\Listman\Db\Member;
use OCA\Listman\Db\Message;
use OCA\Listman\Db\Sendjob;
use OCA\Listman\Db\React;
use OCA\Listman\Db\MemberMapper;
use OCA\Listman\Db\MessageMapper;
use OCA\Listman\Db\SendjobMapper;
use OCA\Listman\Db\ReactMapper;
use OCA\Listman\Cron\ListmanSend;
use OCP\ILogger;

class ListmanService {

  /** @var MaillistMapper */
  private $mapper;
  /** @var SettingsMapper */
  private $settingsMapper;
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
  /** @var IJobList **/
  private $jobList;
  /** @var IFactory */
  private $l10nFactory;
	/** @var ILogger */
	private $logger;


  public function __construct(MaillistMapper $mapper, SettingsMapper $settingsMapper,  MessageMapper $messageMapper, MemberMapper $memberMapper, ReactMapper $reactMapper,  SendjobMapper $sendjobMapper, IURLGenerator $urlGenerator, IFactory $l10nFactory, IJobList  $jobList, ILogger $logger) {
    $this->mapper = $mapper;
    $this->settingsMapper = $settingsMapper;
    $this->memberMapper = $memberMapper;
    $this->messageMapper = $messageMapper;
    $this->sendjobMapper = $sendjobMapper;
    $this->reactMapper = $reactMapper;
    $this->urlGenerator = $urlGenerator;
    $this->jobList = $jobList;
    $this->l10nFactory = $l10nFactory;
		$this->logger = $logger;
  }

  /**
  * Get the reacts to a message
  */
  public function getReactsForMessage($message_id){
    return $this->reactMapper->findAllForMessage($message_id);
  }

  /**
  * Get the permalink
  */
  public function getShareUrl($messageRandid){
    $share = $this->getLink("view",$messageRandid);
    return $share;
  }

  /**
  * The footer at the bottom of the message
  */
  public function getEmailFooter($message,$list){
    $footer = $list->getFooter();
    $html = "";
    $plain= "";
    if(($footer!=null)&&($footer!="")){
      $html.="<p class=\"footer\">".$footer."</p>";
      $plain.="\n$footer\n";
    }
    return ['html'=>$html,'plain'=>$plain]; 
  }

	public function getButtonClass(){
	  return "class=\"btn\" style=\"background: rgb(28,24,96); font-size:1.1em; background: linear-gradient(171deg, rgba(28,24,96,1) 0%, rgba(120,141,169,1) 8%, rgba(41,77,125,1) 23%, rgba(33,49,116,1) 52%, rgba(22,28,88,1) 100%); text-decoration: none; margin: 0.1px 0.3em; border: 2px solid black; border-radius: 1em; box-shadow: 2px 2px 0.3em rgba(.0,.0,.0,.8); text-shadow: 2px 2px 0.2em rgba(.0,.0,.0,.8); color: white; padding: 0.1em 1em; line-height:2.5em;\"";
	}

  /**
  * The buttons that go on a message
  */
  public function getEmailButtons($message,$list){
    $btn = $this->getButtonClass();
    $subscribe = $this->getLink("subscribe",$list->getRandid());
    $share = $this->getLink("view",$message->getRandid());
    $reply = $list->getButtonlink();
    if($reply==""){
      "mailto:".$list->getFromemail();
    }
    $html = "";
    $html.="<div style=\"text-align:center\">";
    $html.="<a $btn href=\"$share?r=❤\">❤</a>";
    $html.="<a $btn href=\"$share?r=👍\">👍</a>";
    $html.="<a $btn href=\"$share?r=👎\">👎</a>";
    $html.="<a $btn href=\"$share?r=😆\">😆</a>";
    $html.="<a $btn href=\"$share?r=😢\">😢</a>";
    $html.="<a $btn href=\"$share?r=😮\">😮</a>";
    $html.="<br clear=\"both\"/>";
    $html.="<a $btn href=\"$subscribe\">Un/Subscribe</a></li>";
    $html.="<a $btn href=\"$share\">Share</a></li>";
    $html.="<a $btn href=\"$reply\">".$list->getButtontext()."</a></li>";
    $html.="</ul>";

    $plain = "";
    $plain.=" * Un/Subscribe: $subscribe\n";
    $plain.=" * Share: $share\n";
    $plain.=" * ".$list->getButtontext().": $reply\n";
    $plain.="---\n\n";
    return ['html'=>$html,'plain'=>$plain]; 
  }


  /**
  * Convert a message into plain and HTML,
  * interpreting the link commands and stuff
  */
  function messageRender($message,$list){
    $html="";
    $plain="";

		//Title
    $html.='<div class="messageHeaders">';
    $html.="<h1>".$message->getSubject()."</h1>";
    $html.="<hr/>";

    $plain.="# ".$message->getSubject()."\n";
    $plain.="---\n";

    //Render the actual text of the message body.
    $pstyle = "style=\"margin-bottom:1em;\"";
    $html.= "<p $pstyle>";

    $body = $message->getBody();
    $lines = explode("\n",$body);

    foreach($lines as $p){
      if($p==""){
        $html.="</p><p $pstyle>";
        $plain.="\n\n";
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
                $html.="</p><h$num>$dat_h</h$num>\n<p $pstyle>";
                $indent = "";
                for($n=0;$n<intval($num);$n++){
                  $indent.="#";
                }
                $plain.="\n$indent $dat";
              }
              break;

            case "/img":
              $cmd = array_shift($params);
              $img = array_shift($params);
              $alt = implode(" ",$params);
              $alt_h = htmlspecialchars($alt);
              $html.="</p><p style=\"text-align:center\"><a href=\"$img\"><img style=\"width: 30em;\" class=\"inlineimg\" alt=\"$alt\" title=\"$alt\" src=\"$img\"></img></a></p>\n<p $pstyle>";
              $plain.="\n * $img ($alt)\n";
              break;

            case "/*link":
              $cmd = array_shift($params);
              $lnk = array_shift($params);
              $dsc = implode(" ",$params);
              if($dsc==""){$dsc = "Link";}
              $dsc_h = htmlspecialchars($dsc);
              $html.=" <p style=\"margin-left:1em\">* <a href=\"$lnk\" class=\"inlinelnk\">$dsc_h</a></p> ";
              $plain.=" * ($dsc)[ $lnk ]\n";
              break;

            case "/link":
              $cmd = array_shift($params);
              $lnk = array_shift($params);
              $dsc = implode(" ",$params);
              if($dsc==""){$dsc = "Link";}
              $dsc_h = htmlspecialchars($dsc);
              $html.=" <a href=\"$lnk\" class=\"inlinelnk\">$dsc_h</a> ";
              $plain.=" ($dsc)[ $lnk ]";
              break;

            default:
              $html.=htmlspecialchars($p." ");
              $plain.=$p;
              break;
        }
      }
    }
    $html.= "</p>";

    $html.="<br><hr style=\"clear:both;\"/>";
    $plain.="---\n";

		//Action Buttons
    $both = $this->getEmailButtons($message,$list);
    $html.=$both['html'];
    $plain.=$both['plain'];

		//Footer
    $footer = $this->getEmailFooter($message,$list);
    $html.=$footer['html'];
    $plain.=$footer['plain'];

    return ["html"=>$html,"plain"=>$plain];
  }


	public function getSettings(){
		//Default settings
		$settings = [
			'host'=>'',
			'user'=>'',
			'pass'=>'',
			'port'=>'',
			'maxdaily'=>'50',
		];
		$settings = $this->settingsMapper->loadall($settings);
		return $settings;
	}

  /**
   * Settings need to be fetched or setted.
   */
  public function settings(Array $postvars): array{
		$settings = $this->getSettings();
		foreach($settings as $n=>$v){
			if(isset($postvars[$n])){
			  $settings[$n] = $postvars[$n];
			}
		}

		$this->settingsMapper->saveall($settings);
    return $settings;
  }
  

  /**
   * Welcome Message Content, return text and HTML versions.
   */
  private function confirmRender($member,$list,$act): array {
    $btn = "class=\"btn\" style=\"background: rgb(28,24,96); font-size:1.1em; background: linear-gradient(171deg, rgba(28,24,96,1) 0%, rgba(120,141,169,1) 8%, rgba(41,77,125,1) 23%, rgba(33,49,116,1) 52%, rgba(22,28,88,1) 100%); text-decoration: none; margin: 0.1px 0.3em; border: 2px solid black; border-radius: 1em; box-shadow: 2px 2px 0.3em rgba(.0,.0,.0,.8); text-shadow: 2px 2px 0.2em rgba(.0,.0,.0,.8); color: white; padding: 0.1em 1em; line-height:2.5em;\"";

    $link = $this->getConfirmLink($member,$list,$act);
    $actverb = "subscribe";
    if($act=="unsub"){$actverb = "unsubscribe";}

		$ret = ['html'=>'','plain'=>''];

		//Title
		$ret['html'].="<h1>".$list->getTitle()." Subscription</h1>";
		$ret['plain'].="= ".$list->getTitle()." Subscription =\n\n";

		//Greeting
    $ret['html'].= "<p>Hi ".$member->getName().",</p>";
    $ret['plain'].= "Hi ".$member->getName().",\n";

		//Explaination
    $ret['html'].= "<p>Someone (hopefully you) asked to $actverb to the email-list \"".$list->getTitle()."\"</p>";
    $ret['plain'].= "Someone (hopefully you) asked to $actverb to the email-list \"".$list->getTitle()."\"\n";

		//Call to action
    $ret['html'].= "<p>If you want that, then you'll have to confirm by clicking this link:</p>";
    $ret['plain'].= "If you want that, then you'll have to confirm by clicking this link:\n\n";

		//Action button
    $ret['html'].= "<p>If not you should ignore this email.</p>";
    $ret['plain'].= "If not you should ignore this email.\n";

    $btn = $this->getButtonClass();
		$ret['html'].="<p align=\"center\"><a $btn href=\"".$link."\">$actverb</a></p>";
		$ret['plain'].=" * $link\n\n";
	  
    return $ret;
  }

  /**
  * Generate a link to a confirmation page.
  * THESE SHOULD NOT BE INCLUDED IN A GENERAL MESSAGE
  * These are SECRET CODE links
  */
  private function getConfirmLink($member,$list,$act): string{
    if($this->updateMemberConfCode($member)){
      $this->memberMapper->update($member);
    }
    $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.confirm', ['lid'=>$list->getRandid()]);
    $params = "?conf=".$member->getConf()."&act=".$act;
    return  $base.$params;
  }

  /**
  * Generate a link to a public page, no identifying information
  * or random-ids.
  */
  private function getLink($page,$param):string {
		$base = "";
    switch($page){
      case "subscribe":
        $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.subscribe', ['lid'=>$param]);
        break;
      case "view":
        $base = $this->urlGenerator->linkToRouteAbsolute('listman.listman.messageview', ['rid'=>$param]);
      default:
        break;
    }
    return  $base;
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
    $now = new \Datetime(); 
    $conf = $this->randId(32);
    $member = new Member();
    $member->setEmail($email);
    $member->setName($name);
    $member->setState($state);
    $member->setListId($list_id);
    $member->setUserId($userId);
    $member->setConf($conf);
    $member->setConfExpire($now->format("Y-m-d H:i:s"));
    return $this->memberMapper->insert($member);
  }
  /**
  * Create a new message
  */
  public function createMessage($subject, $body, $list_id, $userId) {
    $rid = $this->randId(12);
    $now = new \DateTime();
    $message = new Message();
    $message->setSubject($subject);
    $message->setCreatedAt($now->format("Y-m-d H:i:s"));
    $message->setSendrate(1);
    $message->setBody($body);
    $message->setListId($list_id);
    $message->setUserId($userId);
    $message->setRandid($rid);
    return $this->messageMapper->insert($message);
  }

  /**
  * Create a new list
  */
  public function create($title, $desc, $redir, $fromname, $fromemail, $buttontext, $buttonlink, $footer, $userId) {
    $randid = $this->randId();
    $list = new Maillist();
    $list->setRandid($randid);
    $list->setTitle($title);
    $list->setDesc($desc);
    $list->setRedir($redir);
    $list->setFromname($fromname);
    $list->setFromemail($fromemail);
    $list->setButtontext($buttontext);
    $list->setButtonlink($buttonlink);
    $list->setFooter($footer);
    $list->setUserId($userId);
    return $this->mapper->insert($list);
  }

  /**
  * Update existing list
  */
  public function update($id, $title, $desc, $redir, $fromname, $fromemail, $buttontext, $buttonlink, $footer, $userId) {
    try {
      $list = $this->mapper->find($id, $userId);
      $list->setTitle($title);
      $list->setDesc($desc);
      $list->setRedir($redir);
      $list->setFromname($fromname);
      $list->setFromemail($fromemail);
      $list->setButtontext($buttontext);
      $list->setButtonlink($buttonlink);
      $list->setFooter($footer);
      return $this->mapper->update($list);
    } catch (Exception $e) {
      $this->handleException($e);
    }
  }

  /** 
  * Update the conf code
  */
  public function updateMemberConfCode($member){
		$now = new \DateTime();
		$then = new \DateTime();
		$then->setTimestamp($then->getTimestamp()-12*60*60);
		if($member->getConfExpire() < $then->format("Y-m-d H:i:s")){
      $conf = $this->randId(32);
      $member->setConf($conf);
      $member->setConfExpire($now->format("Y-m-d H:i:s"));
      return true;
    }
    return false;
  }

  /**
  * Update existing member
  */
  public function updateMember($id, $email, $name, $state,$list_id,$userId) {
    try {
      $now = new \Datetime(); 
      $member = $this->memberMapper->find($id,$userId);
      $member->setEmail($email);
      $member->setName($name);
      $member->setState($state);
      $member->setListId($list_id);
      $this->updateMemberConfCode($member);
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
      $this->messageMapper->delete($message);
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
    if($mid!=null){
      $current = $this->sendjobMapper->getMessageSentData($mid);
    }else{
      $current = null;
    }
    $queued = $this->sendjobMapper->countAllQueued();
    $rate = $this->messageMapper->getSumSendRate();
    return ["current"=>$current,"all"=>['queued'=>intval($queued),'rate'=>intval($rate)]];
  }

  /**
  * Return a message object
  */
  public function getMessageEntity(int $mid): object{
    return $this->messageMapper->find($mid);
  }

  /**
  * Return a message object
  */
  public function getMessageEntityByRandId(string $rid): object{
    return $this->messageMapper->findByRandid($rid);
  }

  /**
  * Return a List object
  */
  public function getListEntity(int $lid, string $userId): object{
    return $this->mapper->find($lid, $userId);
  }

  /**
  * Send the message to everyone on the list.
  * who is also confirmed, obviously, not litterally
  * everyone. 
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
      $members = $this->memberMapper->findMembers($list->getId(),$userId,1);
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
        $sendJob = new Sendjob();
        $sendJob->setMemberId($member->getId());
        $sendJob->setMessageId($message->getId());
        $sendJob->setState(0);
        $this->sendjobMapper->insert($sendJob);
      }
    }
    //Start slowly
    $message->setSendrate(1);
    $this->messageMapper->update($message);

    $this->jobList->remove(ListmanSend::class);
    $this->jobList->add(ListmanSend::class);
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

  public function formValid($email,$name,$robo){
      if($email==null) return "No Email";
      if($email=="") return "No Email";
      if($name=="") return "No Name";
      if((!strcasecmp($robo,"six")==0)&&
         (!strcasecmp($robo,"6")==0)){
        return "Failed robot check";
      }
      if($name==null) return false;
			$pos = strpos($email,"@");
			if($pos == false){
				return "bad email";
			}
      return "yes";
  }

	/**
	* Check the sending limits for this user,
  * list and generally all-over
	*/
	private function checkSendingLimits($list,$member,$message=null){
    //There's a overall per-day limit.
    $today = new \DateTime();
    $today = $today->format("Y-m-d");
    $sentToday = 0;
    $dbtoday = $this->settingsMapper->getSettingVal("today","");
    if($today==$dbtoday){
      $maxToSend = intval($this->settingsMapper->getSettingVal("maxdaily","50"));
      $sentToday = intval($this->settingsMapper->getSettingVal("senttoday",0));
      if($sentToday>$maxToSend){
        return false;
      }
      $sentToday+=1;
      $this->settingsMapper->setSettingVal("senttoday","".$sentToday);
    }

    //There's an hourly per-person limit.
		$hourAgo = new \DateTime();
	  $hourAgo->setTimestamp($hourAgo->getTimestamp()-60*60);
	  $hourAgo=$hourAgo->format("Y-m-d H:i:s");
		$lastSend = $member->getLastsend();
		if($lastSend > $hourAgo){
			return false;
		}

    return true;
  }

	/**
	* Get a mailer with SMTP details etc.
	*/
	private function getMailer($list,$member,$message=null){
    if(!$this->checkSendingLimits($list,$member)){
      return null;
    }

		//Log this sending
		$now = new \DateTime();
		$member->setLastsend($now->format("Y-m-d H:i:s"));
		$this->memberMapper->update($member);

		require __DIR__.'/../../vendor/autoload.php';
		$settings = $this->getSettings();
		$mail = new PHPMailer(true);
		$mail->CharSet = 'UTF-8';
		$mail->isSMTP();                       
		$mail->Host       = $settings['host'];
		$mail->Username   = $settings['user'];
		$mail->Password   = $settings['pass'];
		$mail->Port       = $settings['port'];
		$mail->SMTPAuth   = true;
		$mail->isHTML(true);
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->addCustomHeader('List-Unsubscribe','<'.$unsub.'>');
		$mail->addCustomHeader('List-Unsubscribe-Post','List-Unsubscribe=One-Click');
		$mail->setFrom($list->getFromemail(), $list->getFromname());
		return $mail;
	}


  /**
  * Send a confirmation email
  */
  private function sendConfirmationEmail($member,$list=null,$act="sub"){
    if($list==null){
      //Ugh. Overflow. Which list was it they want again?
      try{
        $list = $this->mapper->find($member->getListId(),"");
      }catch(Exception $e){
        return false;
      }
    } 

    $sentOkay = false;
    try{
      $content = $this->confirmRender($member,$list,$act);
      $mail = $this->getMailer($list,$member,null);
      if($mail==null){
        //Sending limits passed. 
        $sentOkay=false;
      }else{
        $mail->addAddress($member->getEmail(),$member->getname());
        $mail->Subject = $list->getTitle()." subscription";
        $mail->Body    = $content['html'];;
        $mail->AltBody = $content['plain'];
        $mail->send();
        $sentOkay=true;
      }
    }catch (Exception $e) {
      $sentOkay=false;
    }
    return $sentOkay;
  }



  /**
  * A subscribe action to be used from forms from
  * other sites. We will require a confirmation email.
  * whatever status we are switching to.
  */
  public function subscribe(string $lrid): object {
    $robo  = "";     if(isset($_POST['hello'] )){$robo = $_POST['hello'] ;}
    $name  = "";     if(isset($_POST['name'] )){$name  = $_POST['name'] ;}
    $email = null;   if(isset($_POST['email'])){$email = $_POST['email'];}
    $conf  = "";     if(isset($_POST['conf'] )){$conf  = $_POST['conf'] ;}
    $act   = "sub";  if(isset($_POST['act']  )){$act   = $_POST['act']  ;}
    $redir = null;   if(isset($_POST['redir'])){$redir = $_POST['redir'];}
    if($redir == "{{Your Return URL}}"){    //They didn't bother to fill it in.
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
      $response = new PublicTemplateResponse(Application::APP_ID, 'notfound', ['message'=>"Can't find list"]);
      \OCP\Util::addStyle(Application::APP_ID, 'pub');
      $response->setHeaderTitle('Not Found');
      $response->setHeaderDetails('Dunno what that list is');
      return $response;
    }

		if($email!==null){
		  $valid = $this->formValid($email,$name,$robo);
			if($valid!="yes"){
				$sub = $this->getLink("subscribe",$list->getRandid());
				$response = new PublicTemplateResponse(Application::APP_ID, 'cantsend', ['message'=>$valid,"list"=>$list,"sub"=>$sub]);
				\OCP\Util::addStyle(Application::APP_ID, 'pub');
				$response->setHeaderTitle('Not Found');
				$response->setHeaderDetails('You can\'t spell your own email address');
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
				$conf = $this->randId(32);
				$now = new \Datetime(); 
				$member = new Member();
				$member->setEmail($email);
				$member->setName($name);
				$member->setConf($conf);
				$member->setConfExpire($now->format("Y-m-d H:i:s"));
				$state = 0;
				$member->setState($state);       #Unconfirmed
				$member->setListId($lid);
				$member->setConf($this->randId(32));
				$member->setUserId($list->getUserId());
				$member = $this->memberMapper->insert($member);
			}

			if($this->updateMemberConfCode($member)){
				$this->memberMapper->update($member);
			}

			//Send the actual email.
      $sentOkay = $this->sendConfirmationEmail($member,$list,$act);
			if(!$sentOkay){
        $member->setState(-2);       #Needs confirmation-email resend
        $member = $this->memberMapper->update($member);
        $response = new PublicTemplateResponse(Application::APP_ID, 'cantsend', [
          'message'=>"<p>I am unable to send your confirmation message right away.</p><p>Sorry.</p><p>I have a limt on the number of emails I can send per day to prevent accidentally spamming anyone.</p><p>I have passed that limit.</p><p>Thanks for your patience.</p><p>I will send it tomorrow.</p><p>Looking forward to having you on board.</p><p>Or....  Off-board, if this was an unsubscribe. 😔</p>",
					'headline'=>"A short delay",
          'list'=>$list,
        ]);
        $response->setHeaderTitle('Really Sorry');
        $response->setHeaderDetails('I\'m only allowed a certain number of emails per day');
        \OCP\Util::addStyle(Application::APP_ID, 'pub');
        return $response;
			}

			//All good, so we redirect to owner's URL?
			if($redir!=null){
				return new RedirectResponse($redir);
			}

			//Thanks, that's it.
			$response = new PublicTemplateResponse(Application::APP_ID, 'thanks', [
				'email'=>$email,
				'name'=>$name,
				'act'=>$act,
				'url'=>"",
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
      'url'=>"",
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
      $response = new PublicTemplateResponse(Application::APP_ID, 'badconfirm', [
        'list'=>null,
        'sub'=>null,
        'message'=>"Can't find that list",
      ]);
      $response->setHeaderTitle('Uknown list - error');
      $response->setHeaderDetails('Uhho, trouble.');
      \OCP\Util::addStyle(Application::APP_ID, 'pub');
      return $response;
    }
    $sub = $this->getLink("subscribe",$list->getRandid());

    $member = null;
    try {
      $member = $this->memberMapper->findByConf($conf);
    } catch (Exception $e) {
      $response = new PublicTemplateResponse(Application::APP_ID, 'badconfirm', [
        'list'=>$list,
        'sub'=>$sub,
        'message'=>"Confirmation link expired?",
      ]);
      $response->setHeaderTitle($list->getTitle().' - error');
      $response->setHeaderDetails('Uhho, trouble.');
      \OCP\Util::addStyle(Application::APP_ID, 'pub');
      return $response;
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
    $sublink = $this->getConfirmLink($member,$list,"sub");
    $unsublink = $this->getConfirmLink($member,$list,"unsub");
    $response = new PublicTemplateResponse(Application::APP_ID, 'confirmed', [
      'member'=>$member,
      'list'=>$list,
      'act'=>$act,
      'redir'=>$redir,
      'sub'=>$sublink,
      'unsub'=>$unsublink,
    ]);
    $response->setHeaderTitle($list->getTitle().' - confirmed');
    $response->setHeaderDetails('Thanks, the '.$act.' is confirmed.');
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
  * -2 - Limits expired
  */
  public function sendEmailToMember($sendJob){
    $member = $this->memberMapper->find($sendJob->getMemberId());
    $message = $this->messageMapper->find($sendJob->getMessageId());
    $list = $this->mapper->find($message->getListId(),"");
    $unsub = $this->getConfirmLink($member,$list,"unsub");

	  try{
      $content = $this->messageRender($message,$list);
			$mail = $this->getMailer($list,$member,$message);
      if($mail==null){
        //Passed sending-limits.
        return -2; #Paused
      }
      $mail->addAddress($member->getEmail(),$member->getname());
			$mail->Subject = $message->getSubject()." [".$list->getTitle()."]";
			$mail->Body    = $content['html'];;
			$mail->AltBody = $content['plain'];
			$mail->send();
		}catch (Exception $e) {
			return -1;
		}

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
    $allowedToDo = intval($this->settingsMapper->getSettingVal("maxdaily","0"));
    $tried = 0;

    //Uh.. Yawn. What day is it?
    $today = new \DateTime();
    $today = $today->format("Y-m-d");
    //But..!? I thought it was....
    $dbtoday = $this->settingsMapper->getSettingVal("today","");

    if($today!=$dbtoday){
      //Oh! It's tomorrow! All our limits get reset! Hurray!
      $this->settingsMapper->setSettingVal("today",$today);
      $this->settingsMapper->setSettingVal("senttoday","0");

      //Oh 😟 It's tomorrow. We have to do yesterday's overflow..
      $confirmations = $this->memberMapper->getOverflow();
      foreach($confirmations as $member){
        if($tried < $allowedToDo){
          $done = $this->sendConfirmationEmail($member,null,"sub");
          $tried++;
          if($done){
						$member->setState(0);
      			$this->memberMapper->update($member);
          }  
        }
      }

			//Overflowed send-jobs just have their state changed to be retried today.
      $queuedjobs = $this->sendjobMapper->resetOverflow();
    }

		//Now we process the queue as usual.    
    $jobList = $this->sendjobMapper->getListToSend();
    foreach($jobList as $job){
      if($tried < $allowedToDo){
				$tried++;
				try{
					$state = $this->sendEmailToMember($job);
					$job->setState($state);
					$this->sendjobMapper->update($job);
				}catch(Exception $e){
					$this->logger->logException($e,['message'=>"Exception during queued mail sending"]);
					return false;
				}
			}
    }
    return true;
  }


  /**
  * Register a reaction, which may include a simple
  * page-load
  */
  public function registerReaction($message,$r){
    $allowed = ["😆",";)","x","❤", "👍", "👎", "🤣", "😢", "😮", "🚀", "🤘", "🖖", "👽", "😃", "😁", "😇", "🤪", "👌", "👏", "👐", "🐙", "✊", "👊", "🙃", "😉", "😘"];
    if(!in_array($r,$allowed)){
      $r = "📃";
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



