<?php
namespace OCA\Listman\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class SettingsMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'listman_settings', Settings::class);
    }

	/**
	 * @param int $id
	 * @return Entity|Settings
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
   * We load settings from the DB. Still only one
   * for every key in the array passed.
	 * @param Array $settings
	 * @return Array
	 */
   public function loadall($settings) {
      $ret = [];
      foreach($settings as $n=>$v){
        try{
          $qb = $this->db->getQueryBuilder();
          $qb->select('*')
             ->from($this->getTableName())
             ->where($qb->expr()->eq('settingname', $qb->createNamedParameter($n)));
          $set = $this->findEntity($qb);
        }catch(\Exception $e){
          $set = null;
        }
        if($set!=null){
          $ret[$n] = $set->getSettingvalue();
        }else{
          $ret[$n] = $v;
        }
      }
      return $ret;
   }

	/**
   * We load settings from the DB. Still only one
   * for every key in the array passed.
	 * @param Array $settings
	 * @return Array
	 */
   public function saveall($settings) {
      foreach($settings as $n=>$v){
        try{
          $qb = $this->db->getQueryBuilder();
          $qb->select('*')
             ->from($this->getTableName())
             ->where($qb->expr()->eq('settingname', $qb->createNamedParameter($n)));
          $set = $this->findEntity($qb);
        }catch(\Exception $e){
          $set = null;
        }
        if($set!=null){
          $set->setSettingvalue($v);
          $this->update($set);
        }else{
          $set = new Settings();
          $set->setSettingvalue($v);
          $set->setSettingname($n);
          $this->insert($set);
        }
      }
   }



}


