<?php

namespace OCA\Listman\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'listman';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
