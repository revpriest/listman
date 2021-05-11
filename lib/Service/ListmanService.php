<?php

namespace OCA\Listman\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;
use OCA\Listman\Db\Member;
use OCA\Listman\Db\MemberMapper;

class ListmanService {

	/** @var MaillistMapper */
	private $mapper;
	private $memberMapper;

	public function __construct(MaillistMapper $mapper, MemberMapper $memberMapper) {
		$this->mapper = $mapper;
		$this->memberMapper = $memberMapper;
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

	public function findAll(string $userId): array {
		return $this->mapper->findAll($userId);
	}

	public function findAllMembers(int $listId,string $userId): array {
		file_put_contents("data/prelog.txt","Finding All Members in ".$listId."x".json_encode($userId)."\n",FILE_APPEND);
		return $this->memberMapper->findMembers($listId,$userId);
	}

	public function find($id, $userId) {
		try {
			return $this->mapper->find($id, $userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function findMember($id,$userId) {
		try {
			return $this->memberMapper->find($id,$userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function create($title, $desc, $userId) {
		$list = new Maillist();
		$list->setTitle($title);
		$list->setDesc($desc);
		$list->setUserId($userId);
		return $this->mapper->insert($list);
	}
	public function createMember($email, $name, $state,$listId,$userId) {
		$member = new Member();
		$member->setEmail($email);
		$member->setName($name);
		$member->setState($state);
		$member->setListId($listId);
		$member->setUserId($userId);
		return $this->mapper->insert($member);
	}

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
	public function updateMember($id, $email, $name, $state,$listId,$userId) {
		try {
			$member = $this->memberMapper->find($id,$userId);
			$member->setEmail($email);
			$member->setName($name);
			$member->setState($state);
			$member->setListId($listId);
			return $this->memberMapper->update($member);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function delete($id, $userId) {
		try {
			$list = $this->mapper->find($id, $userId);
			$this->mapper->delete($list);
			return $list;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
	public function deleteMember($id,$userId) {
		try {
			$member = $this->memberMapper->find($id,$userId);
			$this->memberMapper->delete($member);
			return $member;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function listmembers(int $lid, string $userId): array {
		file_put_contents("data/prelog.txt","a)Finding Members for $listId $userId\n",FILE_APPEND);
		return $this->memberMapper->findMembers($lid, $userId);
	}
}
