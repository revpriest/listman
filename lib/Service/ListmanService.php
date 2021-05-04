<?php

namespace OCA\Listman\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;

class ListmanService {

	/** @var MaillistMapper */
	private $mapper;

	public function __construct(MaillistMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function findAll(string $userId): array {
		return $this->mapper->findAll($userId);
	}

	private function handleException(Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new ListNotFound($e->getMessage());
		} else {
			throw $e;
		}
	}

	public function find($id, $userId) {
		try {
			return $this->mapper->find($id, $userId);

			// in order to be able to plug in different storage backends like files
		// for instance it is a good idea to turn storage related exceptions
		// into service related exceptions so controllers and service users
		// have to deal with only one type of exception
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

	public function delete($id, $userId) {
		try {
			$list = $this->mapper->find($id, $userId);
			$this->mapper->delete($list);
			return $list;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
}
