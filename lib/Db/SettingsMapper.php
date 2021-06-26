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
	* @param string $name
	* @param string $default
	* @return string|value
	*/
  public function getSettingVal(string $name,string $default=null) {
	  try{
       $qb = $this->db->getQueryBuilder();
       $qb->select('*')
          ->from($this->getTableName())
          ->where($qb->expr()->eq('settingname', $qb->createNamedParameter($name)));
	 		$obj = $this->findEntity($qb);
	 		if($obj){
        $ret = $obj->getSettingvalue();
	 			return $ret;
      }
	  }catch(\Exception $e){
	 	 return $defaut;
	  }
    return $default;
  }

	/**
	* @param string $name
	* @param string $value
	* @return string|value
	*/
  public function setSettingVal(string $name,string $value=null) {
	  try{
       $qb = $this->db->getQueryBuilder();
       $qb->select('*')
          ->from($this->getTableName())
          ->where($qb->expr()->eq('settingname', $qb->createNamedParameter($name)));
	 		$obj = $this->findEntity($qb);
	 		if($obj){
				$obj->setSettingvalue($value);
        $this->update($obj);
	 			return $obj;
	 		}
	  }catch(\Exception $e){
	  }
    $set = new Settings();
    $set->setSettingvalue($value);
    $set->setSettingname($name);
    $this->insert($set);
    return $set;
  }

	/**
	 * @param string $name
	 * @param string $value
	 * @return string|value
	 */
   public function getVal(int $name) {
		 try{
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('settingname', $qb->createNamedParameter($name)));
				$obj = $this->findEntity($qb);
				if($obj){
					return $obj->getSettingValue();
				}
		 }catch(Exception $e){
			 return null;
		 }
     return null;
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


