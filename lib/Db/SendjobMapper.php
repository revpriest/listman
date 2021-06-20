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
           ->where($qb->expr()->eq('message_id', $qb->createNamedParameter($message_id)))
           ->andWhere($qb->expr()->eq('member_id', $qb->createNamedParameter($member_id)));
        return $this->findEntity($qb);
   }


	/**
	 * @return Entity|Sendjob
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
   public function getNextToSend() {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('state', $qb->createNamedParameter(0)))
           ->setMaxResults(1);
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

	/**
   * Count all the queued messages to be sent
	 * @return int
	 */
    public function countAllQueued() {
      $qb = $this->db->getQueryBuilder();
      $qb->select($qb->func()->count('*'))
         ->from($this->getTableName())
         ->where($qb->expr()->eq('state',$qb->createNamedParameter(0)));
			$queued = $qb->execute()->fetchOne();
      return $queued;
    }

	/**
   * Count all the queued messages to be sent
	 * @return int
	 */
    public function getCurrentRate() {
      return 1;
    }

	/**
	 * @param int $id message ID
	 * @return array
	 */
    public function getMessageSentData(int $id) {
      $qb = $this->db->getQueryBuilder();
      $qb->select($qb->func()->count('*'))
         ->from($this->getTableName())
         ->where($qb->expr()->eq('message_id', $qb->createNamedParameter($id)))
         ->andWhere($qb->expr()->eq('state',$qb->createNamedParameter(0)));
			$unsent = $qb->execute()->fetchOne();
      $qb2 = $this->db->getQueryBuilder();
      $qb2->select($qb->func()->count('*'))
         ->from($this->getTableName())
         ->where($qb2->expr()->eq('message_id', $qb2->createNamedParameter($id)))
         ->andWhere($qb2->expr()->neq('state',$qb2->createNamedParameter(0)));
			$sent = $qb2->execute()->fetchOne();
      return ['sent'=>$sent,'queued'=>$unsent];
    }

}


