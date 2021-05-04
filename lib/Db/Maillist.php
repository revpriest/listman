<?php

namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Maillist extends Entity implements JsonSerializable {
	protected $title;
	protected $desc;
	protected $userId;

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'title' => $this->title,
			'desc' => $this->desc
		];
	}
}
