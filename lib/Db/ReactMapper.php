<?php
namespace OCA\Listman\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class ReactMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'listman_react', React::class);
    }

	/**
	 * @param int $id
	 * @return Entity|React
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
   public function find(int $id) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($message_id)));
        return $this->findEntity($qb);
   }


	/**
	 * @param int $message_id
	 * @return array
	 */
   public function findAllForMessage(int $message_id) {
     $qb = $this->db->getQueryBuilder();
     $qb->select('*')
         ->from($this->getTableName())
         ->where($qb->expr()->eq('message_id',$qb->createNamedParameter($message_id)))
         ->orderBy("count","desc");
        
		 $ret = $this->findEntities($qb);
     return $ret;
   }

	/**
	 * @param int $message_id
	 * @return array
	 */
   public function findByMessageAndSymbol(int $message_id, string $symbol) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('message_id',$qb->createNamedParameter($message_id)))
            ->andWhere($qb->expr()->eq('symbol',$qb->createNamedParameter($symbol)));
				$ret = $this->findEntity($qb);
        return $ret;
   }
}


