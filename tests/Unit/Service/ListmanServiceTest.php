<?php

namespace OCA\Listman\Tests\Unit\Service;

use OCA\Listman\Service\ListNotFound;
use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Db\DoesNotExistException;

use OCA\Listman\Db\List;
use OCA\Listman\Service\MaillistService;
use OCA\Listman\Db\ListMapper;

class ListmanServiceTest extends TestCase {
	private $service;
	private $mapper;
	private $userId = 'john';

	public function setUp(): void {
		$this->mapper = $this->getMockBuilder(ListMapper::class)
			->disableOriginalConstructor()
			->getMock();
		$this->service = new MaillistService($this->mapper);
	}

	public function testUpdate() {
		// the existing list
		$list = List::fromRow([
			'id' => 3,
			'title' => 'yo',
			'desc' => 'nope'
		]);
		$this->mapper->expects($this->once())
			->method('find')
			->with($this->equalTo(3))
			->will($this->returnValue($list));

		// the list when updated
		$updatedList = List::fromRow(['id' => 3]);
		$updatedList->setTitle('title');
		$updatedList->setDesc('desc');
		$this->mapper->expects($this->once())
			->method('update')
			->with($this->equalTo($updatedList))
			->will($this->returnValue($updatedList));

		$result = $this->service->update(3, 'title', 'desc', $this->userId);

		$this->assertEquals($updatedList, $result);
	}

	public function testUpdateNotFound() {
		$this->expectException(ListNotFound::class);
		// test the correct status code if no list is found
		$this->mapper->expects($this->once())
			->method('find')
			->with($this->equalTo(3))
			->will($this->throwException(new DoesNotExistException('')));

		$this->service->update(3, 'title', 'desc', $this->userId);
	}
}
