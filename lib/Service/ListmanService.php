<?php

namespace OCA\Listman\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IURLGenerator;

use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;
use OCA\Listman\Db\Member;
use OCA\Listman\Db\MemberMapper;

class ListmanService {

	/** @var MaillistMapper */
	private $mapper;
	/** @var MemberMapper */
	private $memberMapper;
	/** @var IURLGenerator */
	protected $urlGenerator;

	public function __construct(MaillistMapper $mapper, MemberMapper $memberMapper, IURLGenerator $urlGenerator) {
		$this->mapper = $mapper;
		$this->memberMapper = $memberMapper;
		$this->urlGenerator = $urlGenerator;
	}

  /**
   * Welcome Message Content.
   */
  private function getWelcomeContent($member,$list,$act): string {
    $ret = "Hi ".$member->getName().",\n";
    $ret = "Someone (hopefully you) asked to be ";
    $ret.= ($act=="sub"?"subscribed":"unsubscribed");
    $ret.= " to the email list \"".$list->getTitle()."\"\n";
    $ret.= "\n";
    $ret.= "If you want that, then you'll have to confirm by clicking this link:\n";
    $ret.= $this->getConfirmLink($member,$list,$act)."\n";
    $ret.= "\n";
    return $ret;
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
	private function sendEmail($member,$content){
     //No actual emailing stuff yet, so we just send to log
    file_put_contents("data/prelog.txt","Emailing ".$member->getEmail()." with:\n".$content,FILE_APPEND);
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
  * Create a new list
  */
	public function create($title, $desc, $userId) {
		$randid = $this->randId();
		$list = new Maillist();
		$list->setTitle($title);
		$list->setDesc($desc);
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
  * A subscribe action to be used from forms from
  * other sites. We will require a confirmation email.
  * whatever status we are switching to.
  */
  public function subscribe(string $lrid): array {
    $name  = "John";  if(isset($_POST['name'] )){$name  = $_POST['name'] ;}
    $email = "a@b.c"; if(isset($_POST['email'])){$email = $_POST['email'];}
    $conf  = "xxxx";  if(isset($_POST['conf'] )){$conf  = $_POST['conf'] ;}
    $act   = "sub" ;  if(isset($_POST['act']  )){$act   = $_POST['act']  ;}

    //We have to find the list so we know which Nextcloud user created it.
    $list = null;
    try {
      $list = $this->mapper->findByRandId($lrid);
      $lid = $list->getId();
		} catch (Exception $e) {
			throw new ListNotFound("Can't find that mailling list");
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

    $this->sendEmail($member,$this->getWelcomeContent($member,$list,$act));
    print ("$name &lt;$email&gt; is trying to $act on list $lid with confirmation $conf - already exists: $existed");
    exit;
  }

  /**
  * A confirm action which should only be valid if
  * it has a confirm parameter and an action.
  */
  public function confirm(string $lrid,string $conf, string $act): array {
    $list = null;
    try {
      $list = $this->mapper->findByRandId($lrid);
		} catch (Exception $e) {
			throw new ListNotFound("Can't find that mailling list");
    }

    $member = null;
    try {
      $member = $this->memberMapper->findByConf($conf);
		} catch (Exception $e) {
			throw new ListNotFound("Can't find you in that list");
    }

		if($act=="sub"){
			$member->setState(1);
			$this->memberMapper->update($member);
		}
		if($act=="unsub"){
			$member->setState(-1);
			$this->memberMapper->update($member);
		}

    print("User with conf $conf has $act on list $lrid");
    exit;
  }

}



