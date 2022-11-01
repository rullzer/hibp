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

use OCA\HIBP\Service\HIBPService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;

class UserJob extends TimedJob {

    private HIBPService $HIBPService;

	public function __construct(ITimeFactory $timeFactory, HIBPService $HIBPService) {
		parent::__construct($timeFactory);

		// Run once a day
		$this->setInterval(24 * 60 * 60);

        // This job is not time sensitive run whenever
        $this->timeSensitivity = IJob::TIME_INSENSITIVE;

        $this->HIBPService = $HIBPService;
	}

	protected function run($argument) {
        $userId = $argument[0];

        $this->HIBPService->check($userId);
	}
}
