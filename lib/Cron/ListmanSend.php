<?php
/**
 * We have a cron to check if there's actual send-jobs to
 * do and then do them if there is.
 */

namespace OCA\Listman\Cron;

use OCP\BackgroundJob\IJobList;
use OCA\Listman\Service\ListmanService;
use \OCP\BackgroundJob\Job;
use \OCP\AppFramework\Utility\ITimeFactory;


class ListmanSend extends Job {
	/** @var ListmanService */
	private $service;
	/** @var IJobList */
	private $jobList;

	public function __construct(ITimeFactory $time, ListmanService $service, IJobList $jobList) {
    parent::__construct($time);
		$this->service = $service;
		$this->jobList = $jobList;
	}

	protected function run($arguments) {
		$this->service->runCron();
	}
}
