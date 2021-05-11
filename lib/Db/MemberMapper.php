<?php
namespace OCA\Listman\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class MemberMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'listman_member', Member::class);
    }

	/**
	 * @param int $id
	 * @return Entity|Member
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
    public function find(int $id) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        return $this->findEntity($qb);
    }

	/**
	 * @param string $userId
	 * @return array
	 */
    public function findAll(string $userId) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
				$ret = $this->findEntities($qb);
        return $ret;
    }

	/**
	 * @param int $list_id
	 * @param string $user_id
	 * @return array
	 */
    public function findMembers(int $list_id,string $user_id) {
      $qb = $this->db->getQueryBuilder();
      $qb->select('*')
         ->from($this->getTableName())
         ->where($qb->expr()->andx(
              ($qb->expr()->eq('list_id', $qb->createNamedParameter($list_id))),
              ($qb->expr()->eq('user_id', $qb->createNamedParameter($user_id)))
           ));
			$ret = $this->findEntities($qb);
      return $ret;
    }
}

