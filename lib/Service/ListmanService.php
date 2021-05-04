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

	public function findAllMembers(string $userId): array {
		return $this->memberMapper->findAll(3);
	}

	public function find($id, $userId) {
		try {
			return $this->mapper->find($id, $userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function findMember($id) {
		try {
			return $this->memberMapper->find($id);
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
	public function createMember($email, $name, $state,$listId) {
		$member = new Member();
		$member->setEmail($email);
		$member->setName($name);
		$member->setState($state);
		$member->setListId($listId);
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
	public function updateMember($id, $email, $name, $state,$listId) {
		try {
			$member = $this->memberMapper->find($id);
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
	public function deleteMember($id) {
		try {
			$member = $this->mapper->find($id);
			$this->memberMapper->delete($member);
			return $member;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
}
