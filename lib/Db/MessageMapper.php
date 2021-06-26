<?php
namespace OCA\Listman\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class MessageMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'listman_message', Message::class);
    }

	/**
	 * @param string $id
	 * @return Entity|Message
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
   public function findByRandid(string $rid) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('randid', $qb->createNamedParameter($rid)));
        return $this->findEntity($qb);
   }


	/**
	 * @param int $id
	 * @return Entity|Message
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
	 * @param int $list_id
	 * @param string $user_id
	 * @return array
	 */
    public function findMessages(int $list_id,string $user_id) {
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
    * List all the messages that have sendrate > 0
    */
   public function findRunningMessages(){
      try{
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from("listman_message")
           ->where($qb->expr()->gt('sendrate', $qb->createNamedParameter(0)));
        $messages = $this->findEntities($qb);
        return $messages;
      }catch(\Exception $e){
      }
      return [];
   }

	/**
   * Count all the queued messages to be sent
	 * @return int
	 */
    public function getSumSendRate() {
      $qb = $this->db->getQueryBuilder();
      $qb->select($qb->func()->sum('sendrate'))
         ->from($this->getTableName())
         ->where($qb->expr()->gt('sendrate',$qb->createNamedParameter(0)));
			$rate = $qb->execute()->fetchOne();
      return $rate;
    }
}


