<?php
namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Member extends Entity implements JsonSerializable {
    protected $name;
    protected $email;
    protected $state;
    protected $listId;
    protected $userId;

    public function __construct() {
        $this->addType('id','integer');
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'state' => $this->state,
            'list_id' => $this->listId,
        ];
    }
}


