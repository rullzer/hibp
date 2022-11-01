<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\HIBP\Backgroundjob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUser;
use OCP\IUserManager;

class UserInjector extends TimedJob {

	private IUserManager $userManager;
	private IJobList $jobList;


	public function __construct(ITimeFactory $timeFactory, IUserManager $userManager, IJobList $jobList) {
		parent::__construct($timeFactory);

		$this->userManager = $userManager;
		$this->jobList = $jobList;

		// Run once every 30 days
		$this->setInterval(30 * 24 * 60 * 60);

		// This job is not time sensitive run whenever
        $this->timeSensitivity = IJob::TIME_INSENSITIVE;
	}

	protected function run($argument) {
		\OC::$server->getLogger()->error("RUNNING");
		$this->userManager->callForSeenUsers(function (IUser $user): bool {
			$this->jobList->add(UserJob::class, [$user->getUID()]);
			return true;
		});
	}


}
