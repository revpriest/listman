<?php
namespace OCA\Listman\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCA\Listman\Db\Message;
use OCA\Listman\Db\MessageMapper;

class SendjobMapper extends QBMapper {
    /** @var MessageMapper */
    private $messageMapper;
    private $maxSendrate=20;

    public function __construct(IDBConnection $db,MessageMapper $messageMapper) {
        parent::__construct($db, 'listman_sendjob', Sendjob::class);
		    $this->messageMapper = $messageMapper;
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
   * List of jobs. Well we need to find messages which are
   * sending. They have a sendrate>=1. We can then look for
   * sendjobs with that messageid and state=0, limit by the
   * sendrate. We then increment the sendrate and update. 
	 * @return Entity|Sendjob
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
   public function getListToSend() {
        $ret = [];
        $messages = $this->messageMapper->findRunningMessages();

        file_put_contents("data/prelog.txt","Mess: ".json_encode($messages)."\n",FILE_APPEND);
        foreach($messages as $message){
          $sr = $message->getSendrate();
          $qb = $this->db->getQueryBuilder();
          $qb->select('*')
             ->from($this->getTableName())
             ->where($qb->expr()->eq('state', $qb->createNamedParameter(0)))
             ->andWhere($qb->expr()->eq('message_id', $qb->createNamedParameter($message->getId())))
             ->orderBy('member_id')
             ->setMaxResults($sr);
          $jobs = $this->findEntities($qb);
          file_put_contents("data/prelog.txt","Jobs: ".json_encode($jobs)."\n",FILE_APPEND);
          if(sizeof($jobs)<=0){
            $message->setSendrate(0);
            $this->messageMapper->update($message);
          }else{
            foreach($jobs as $job){
              $ret[] = $job;
            }
            if($sr<$this->maxSendrate){
              $message->setSendrate($sr+1);
              $this->messageMapper->update($message);
            }
          }
        } 
        return $ret;
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

      $qb = $this->db->getQueryBuilder();
      $qb->select($qb->func()->count('*'))
         ->from($this->getTableName())
         ->where($qb->expr()->eq('message_id', $qb->createNamedParameter($id)))
         ->andWhere($qb->expr()->neq('state',$qb->createNamedParameter(0)));
			$sent = $qb->execute()->fetchOne();

      return ['sent'=>$sent,'queued'=>$unsent];
    }

}


