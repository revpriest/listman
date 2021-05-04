<?php

namespace OCA\Listman\Controller;

use OCA\Listman\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Render default template.
   * This is the page which has the web-app code from src/App.vue
   * It includes the stylesheet from css/style.css
	 */
	public function index() {
		Util::addScript(Application::APP_ID, 'listman-main');
		Util::addStyle($this->appName, 'icons');
		Util::addStyle($this->appName, 'style');
		return new TemplateResponse(Application::APP_ID, 'main');
	}
}
