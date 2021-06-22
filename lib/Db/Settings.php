<?php
namespace OCA\Listman\Db;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Settings extends Entity implements JsonSerializable {
    protected $state;
    protected $settingname;
    protected $settingvalue;

    public function __construct() {
    }

    public function jsonSerialize() {
        return [
			      'id' => $this->id,
            'settingname' => $this->settingname,
            'settingvalue' => $this->settingvalue,
        ];
    }
}



