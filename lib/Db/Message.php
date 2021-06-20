<?php
namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Message extends Entity implements JsonSerializable {
    protected $body;
    protected $subject;
    protected $listId;
    protected $createdAt;
    protected $userId;
    protected $sendrate;

    public function __construct() {
        $this->addType('id','integer');
        $this->sendrate=0;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'list_id' => $this->listId,
            'created_at' => $this->createdAt,
            'subject' => $this->subject,
            'body' => $this->body,
            'user_id' => $this->userId,
            'sendrate' => $this->sendrate,
        ];
    }
}



