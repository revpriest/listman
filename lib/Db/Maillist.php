<?php

namespace OCA\Listman\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Maillist extends Entity implements JsonSerializable {
	protected $title;
	protected $desc;
  protected $fromname;
  protected $fromemail;
  protected $buttontext;
  protected $shareurl;
  protected $suburl;
  protected $buttonlink;
  protected $footer;
	protected $randid;
	protected $redir;
	protected $sharelinks;
	protected $userId;

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'title' => $this->title,
			'desc' => $this->desc,
      'fromname' => $this->fromname,
      'fromemail' => $this->fromemail,
      'buttontext' => $this->buttontext,
      'buttonlink' => $this->buttonlink,
      'footer' => $this->footer,
      'shareurl' => $this->shareurl,
      'suburl' => $this->suburl,
			'randid' => $this->randid,
			'redir' => $this->redir,
			'sharelinks' => $this->sharelinks,
			'userId' => $this->userId,
		];
	}
}
