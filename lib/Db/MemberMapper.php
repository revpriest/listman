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
	 * @param int $listId
	 * @param string $userId
	 * @return array
	 */
    public function findMembers(int $listId,string $user_id) {
		  file_put_contents("data/prelog.txt","Finding Members for $listId $user_id\n",FILE_APPEND);
      $qb = $this->db->getQueryBuilder();
      $qb->select('*')
         ->from($this->getTableName())
         ->where($qb->expr()->andx(
              ($qb->expr()->eq('listId', $qb->createNamedParameter($listId))),
              ($qb->expr()->eq('user_id', $qb->createNamedParameter($user_id)))
           ));
			$ret = $this->findEntities($qb);
      return $ret;
    }
}

