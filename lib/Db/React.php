<?php
namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class React extends Entity implements JsonSerializable {
    protected $messageId;
    protected $symbol;
    protected $count;

    public function __construct() {
    }

    public function jsonSerialize(): array {
        return [
			      'id' => $this->id,
            'message_id' => $this->messageId,
            'symbol' => $this->symbol,
            'count' => $this->count,
        ];
    }
}



