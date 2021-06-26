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
	 * @param string $conf
	 * @return Entity|Member
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function findByConf(string $conf): Member {
    $expire = new \DateTime();
    $expire->sub(new \DateInterval("P1D"));

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('listman_member')
			->where($qb->expr()->eq('conf', $qb->createNamedParameter($conf)))
			->andWhere($qb->expr()->gt('conf_expire', $qb->createNamedParameter($expire->format("Y-m-d H:i:s"))));
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
	 * @param string $userId
	 * @return array
	 */
  public function getOverflow() {
      try{
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('state', $qb->createNamedParameter(-2)));
        $ret = $this->findEntities($qb);
        return $ret;
      }catch(Exception $e){
        return [];
      }
  }

	/**
	 * @param int $list_id
	 * @param string $user_id
	 * @return array
	 */
    public function findMembers(int $list_id,string $user_id,$state=null) {
      $qb = $this->db->getQueryBuilder();
      $qb->select('*')
         ->from($this->getTableName())
         ->where($qb->expr()->andx(
              ($qb->expr()->eq('list_id', $qb->createNamedParameter($list_id))),
              ($qb->expr()->eq('user_id', $qb->createNamedParameter($user_id)))
           ));
      if($state!=null){
        $qb->andWhere($qb->expr()->eq('state', $qb->createNamedParameter($state)));
      }
			$ret = $this->findEntities($qb);
      return $ret;
    }

	/**
   * Find a member of a list based on their email address.
   * We return null if it can't be found.
	 * @param string $list_id
	 * @param string $email
	 * @return Entity|Member
	 * @throws DoesNotExistException
	 */
    public function findMemberByEmail(int $list_id,string $email) {
      $qb = $this->db->getQueryBuilder();
      $qb->select('*')
         ->from($this->getTableName())
         ->where($qb->expr()->andx(
              ($qb->expr()->eq('list_id', $qb->createNamedParameter($list_id))),
              ($qb->expr()->eq('email', $qb->createNamedParameter($email)))
           ));
      $ret = $this->findEntity($qb);
      return $ret;
    }
}

