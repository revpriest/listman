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
	 * @param string $listId
	 * @return array
	 */
    public function findAll(int $listId) {
        $qb = $this->db->getQueryBuilder();
				file_put_contents("data/prelog.txt","Building a query ".$listId." on ".$this->getTableName()."\n",FILE_APPEND);
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('listId', $qb->createNamedParameter($listId)));
				file_put_contents("data/prelog.txt",json_encode($qb)."\n",FILE_APPEND);
				$ret = $this->findEntities($qb);
				file_put_contents("data/prelog.txt","Done a query on ".$this->getTableName()."\n",FILE_APPEND);
        return $ret;
    }
}

