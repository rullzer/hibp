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

use OCA\HIBP\AppInfo\Application;
use OCA\HIBP\Service\HIBPService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\IGroupManager;
use OCP\Notification\IManager;

class KeyReminderJob extends TimedJob {

    private IAppConfig $appConfig;
    private IManager $notificationManager;
    private IGroupManager $groupManager;

	public function __construct(ITimeFactory $timeFactory, IAppConfig $appConfig, IManager $notificationManager, IGroupManager $groupManager) {
		parent::__construct($timeFactory);

		// Run once a week
		$this->setInterval(7 * 24 * 60 * 60);

        // This job is not time sensitive run whenever
        $this->timeSensitivity = IJob::TIME_INSENSITIVE;

        $this->appConfig = $appConfig;
        $this->notificationManager = $notificationManager;
        $this->groupManager = $groupManager;
	}

	protected function run($argument) {
        $apiKey = $this->appConfig->getAppValue('api-key');
        if ($apiKey !== '') {
            // All is right with the world. Carry on
            return;
        }


        $notification = $this->notificationManager->createNotification();

        $adminGroup = $this->groupManager->get('admin');
        foreach ($adminGroup->getUsers() as $user) {
            $notification->setApp(Application::APP_NAME)
                ->setUser($user->getUID())
                ->setDateTime(new \DateTime())
                ->setObject('key', 'missing')
                ->setSubject('key', []);
            
            $this->notificationManager->notify($notification);
        }
	}
}
