<?php

namespace OCA\Listman\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http;
use OCP\IRequest;

use OCA\Listman\Service\NoteNotFound;
use OCA\Listman\Service\ListmanService;
use OCA\Listman\Controller\ListmanController;

class ListmanControllerTest extends TestCase {
	protected $controller;
	protected $service;
	protected $userId = 'john';
	protected $request;

	public function setUp(): void {
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->service = $this->getMockBuilder(ListmanService::class)
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new ListmanController($this->request, $this->service, $this->userId);
	}

	public function testUpdate() {
		$list = 'just check if this value is returned correctly';
		$this->service->expects($this->once())
			->method('update')
			->with($this->equalTo(3),
					$this->equalTo('title'),
					$this->equalTo('desc'),
				   $this->equalTo($this->userId))
			->will($this->returnValue($list));

		$result = $this->controller->update(3, 'title', 'desc');

		$this->assertEquals($list, $result->getData());
	}


	public function testUpdateNotFound() {
		// test the correct status code if no list is found
		$this->service->expects($this->once())
			->method('update')
			->will($this->throwException(new ListNotFound()));

		$result = $this->controller->update(3, 'title', 'desc');

		$this->assertEquals(Http::STATUS_NOT_FOUND, $result->getStatus());
	}
}
