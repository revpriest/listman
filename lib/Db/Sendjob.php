<?php
namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Sendjob extends Entity implements JsonSerializable {
    protected $state;
    protected $messageId;
    protected $memberId;

    public function __construct() {
    }

    public function jsonSerialize() {
        return [
			      'id' => $this->id,
            'member_id' => $this->memberId,
            'message_id' => $this->messageId,
            'state' => $this->state,
        ];
    }
}



