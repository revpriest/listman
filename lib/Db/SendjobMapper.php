<?php
namespace OCA\Listman\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class SendjobMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'listman_sendjob', Sendjob::class);
    }

	/**
	 * @param int $id
	 * @return Entity|Sendjob
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
   public function find(int $message_id,int $member_id) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('message_id', $qb->createNamedParameter($message_id)));
           ->andWhere($qb->expr()->eq('member_id', $qb->createNamedParameter($member_id)));
        return $this->findEntity($qb);
   }


	/**
	 * @param string $userId
	 * @return array
	 */
   public function findAllForMessage(string $message_id) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('message_id', $qb->createNamedParameter($message_id)));
				$ret = $this->findEntities($qb);
        return $ret;
   }
}


