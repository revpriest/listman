<?php

namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Maillist extends Entity implements JsonSerializable {
	protected $title;
	protected $desc;
	protected $randid;
	protected $redir;
	protected $userId;

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'title' => $this->title,
			'desc' => $this->desc,
			'randid' => $this->randid,
			'redir' => $this->redir
		];
	}
}
