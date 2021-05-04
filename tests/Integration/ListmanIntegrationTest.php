<?php

namespace OCA\Listman\Tests\Integration\Controller;

use OCP\AppFramework\App;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;


use OCA\Listman\Db\Maillist;
use OCA\Listman\Db\MaillistMapper;
use OCA\Listman\Controller\ListmanController;

class ListmanIntegrationTest extends TestCase {
	private $controller;
	private $mapper;
	private $userId = 'john';

	public function setUp(): void {
		$app = new App('listman');
		$container = $app->getContainer();

		// only replace the user id
		$container->registerService('userId', function () {
			return $this->userId;
		});

		// we do not care about the request but the controller needs it
		$container->registerService(IRequest::class, function () {
			return $this->createMock(IRequest::class);
		});

		$this->controller = $container->query(ListmanController::class);
		$this->mapper = $container->query(MaillistMapper::class);
	}

	public function testUpdate() {
		// create a new list that should be updated
		$list = new Maillist();
		$list->setTitle('old_title');
		$list->setDesc('old_desc');
		$list->setUserId($this->userId);

		$id = $this->mapper->insert($list)->getId();

		// fromRow does not set the fields as updated
		$updatedList = Maillist::fromRow([
			'id' => $id,
			'user_id' => $this->userId
		]);
		$updatedList->setDesc('desc');
		$updatedList->setTitle('title');

		$result = $this->controller->update($id, 'title', 'desc');

		$this->assertEquals($updatedList, $result->getData());

		// clean up
		$this->mapper->delete($result->getData());
	}
}
