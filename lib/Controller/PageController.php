<?php

namespace OCA\Listman\Controller;

use OCA\Listman\AppInfo\Application;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;

class PageController extends OCSController {
	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Render default template.
   * This is the page which has the web-app code from src/App.vue
   * It includes the stylesheet from css/style.css
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index() {
		Util::addScript(Application::APP_ID, 'main');
		Util::addStyle($this->appName, 'icons');
		Util::addStyle($this->appName, 'style');
		$response = new TemplateResponse(Application::APP_ID, 'main');
		$policy = new ContentSecurityPolicy();
    $policy->addAllowedScriptDomain(['\'unsafe-eval\'','\'unsafe-inline\'','\'unsafe-eval\'','\'script-src\'']);
		$policy->addAllowedImageDomain('*');
    $policy->addAllowedMediaDomain('blob:');
    $policy->addAllowedMediaDomain('data:');
    $policy->addAllowedMediaDomain('self');
    $policy->addAllowedMediaDomain('https://array'); // Add any other needed domains
		// Needed for the ES5 compatible build of PDF.js
		$policy->allowEvalScript(true);
		$response->setContentSecurityPolicy($policy);
    return $response;
	}
}
