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
        $this->addType('listId','integer');
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'listId' => $this->listId,
            'name' => $this->name,
            'email' => $this->email,
            'state' => $this->state,
            'user_id' => $this->user_id,
        ];
    }
}


