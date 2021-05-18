<?php

namespace OCA\Listman\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class MaillistMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'listman_list', Maillist::class);
	}

	/**
	 * @param int $id
	 * @param string $userId
	 * @return Entity|Maillist
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $userId): Maillist {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('listman_list')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntity($qb);
	}

	/**
	 * @param int $id
	 * @return Entity|Maillist
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function findAnyones(int $id): Maillist {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('listman_list')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * @param int $rid
	 * @return Entity|Maillist
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function findByRandId(string $rid): Maillist {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('listman_list')
			->where($qb->expr()->eq('randid', $qb->createNamedParameter($rid)));
		return $this->findEntity($qb);
	}


	/**
	 * @param string $userId
	 * @return array
	 */
	public function findAll(string $userId): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('listman_list')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntities($qb);
	}
}
