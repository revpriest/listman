<?php
namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Member extends Entity implements JsonSerializable {
    protected $name;
    protected $email;
    protected $state;
    protected $conf;
    protected $confExpire;
    protected $lastsend;
    protected $listId;
    protected $userId;

    #States:
    # 0 - Unconfirmed
    # 1 - Subscribed
    # 2+ - Reserved for sub-groups
    #-1 - Blocked
    #-2 - Delay in sending confirmation, resend tomorrow.

    public function __construct() {
        $this->addType('id','integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'state' => $this->state,
            'conf' => $this->conf,
            'confExpire' => $this->confExpire,
            'lastsend' => $this->lastsend,
            'list_id' => $this->listId
        ];
    }
}


