<?php
namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Sendjob extends Entity implements JsonSerializable {
    protected $state;
    protected $messageId;
    protected $memberId;

    #States:
    #-2 - Needs resend after limit reached
    #-1 - Failed to send
    # 0 - queueud
    # 1 - Sent

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



