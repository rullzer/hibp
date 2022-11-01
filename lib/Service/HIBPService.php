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

namespace OCA\HIBP\Service;

use GuzzleHttp\Exception\RequestException;
use OCA\HIBP\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClientService;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class HIBPService {

    private IUserManager $userManager;
    private IClientService $clientService;
    private IAppConfig $appConfig;
    private ICrypto $crypto;
    private IManager $notificationManager;
    private LoggerInterface $logger;

    public function __construct(
        IUserManager $userManager, 
        IClientService $clientService, 
        IAppConfig $appConfig, 
        ICrypto $crypto, 
        IManager $notificationManager,
        LoggerInterface $logger
    ) {
        $this->userManager = $userManager;
        $this->clientService = $clientService;
        $this->appConfig = $appConfig;
        $this->crypto = $crypto;
        $this->notificationManager = $notificationManager;
        $this->logger = $logger;
    }


    public function check(string $userId) {
        $user = $this->userManager->get($userId);

        if ($user === null) {
            return;
        }

        $email = $user->getEMailAddress();
        if ($email === null) {
            return;
        }

        $apiKey = $this->appConfig->getAppValue('api-key');
        if ($apiKey === '') {
            return;
        }

        try {
            $apiKey = $this->crypto->decrypt($apiKey);
        } catch (\Exception $e) {
            return;
        }

        $client = $this->clientService->newClient();

        // Get the breaches
        $url = 'https://haveibeenpwned.com/api/v3/breachedaccount/' . $email;

        // Silly for loop to try at most 10 times
        for ($i = 0; $i < 10; $i++) {
            $this->logger->debug('Attempt ' . $i . ' for ' . $email);

            try {
                $resp = $client->get($url, [
                    'headers' => [
                        'hibp-api-key' => $apiKey,
                        'User-Ager' => 'hibp Nextcloud app',
                    ],
                ]);
                $this->logger->debug('Attempt successfull');
                break;
            } catch (RequestException $e) {
                $this->logger->debug('Attempt failed');
                if ($e->hasResponse()) {
                    $resp = $e->getResponse();
                    if ($resp->getStatusCode() === 400) {
                        // Malformed url
                        return;
                    } else if ($resp->getStatusCode() === 401) {
                        // Bad API!
                        return;
                    } else if ($resp->getStatusCode() === 403) {
                        // Should never happen as we set the user agent
                        return;
                    } else if ($resp->getStatusCode() === 404) {
                        // Not found. This account has not been pwned yet!
                        // So nothign to do... carry on
                        return; 
                    } else if ($resp->getStatusCode() === 429) {
                        // Hit the rate limit!
                        $this->logger->debug('We hit the rate limit!');
                        $delay = (int)$resp->getHeader('retry-after')[0] + 1;
                        sleep($delay);
                        continue;
                    } else if ($resp->getStatusCode() === 503) {
                        // Something internal did boom. Better luck next time
                        return;
                    }
                }
            }
        }

        if ($resp->getStatusCode() !== 200) {
            return;
        }

        $body = $resp->getBody();

        $prevData = $this->appConfig->getAppValue('user-' . $userId);

        $this->logger->debug("previous data: " . $prevData);

        if ($prevData === '') {
            $prevData = [];
        } else {
            $prevData = json_decode($prevData, true);
        }

        $newData = json_decode($body, true);
        $newData = array_map(function($breach) { return $breach["Name"]; }, $newData);

        $diff = array_diff($newData, $prevData);

        foreach ($diff as $breach) {
            $notification = $this->notificationManager->createNotification();

            $notification->setApp(Application::APP_NAME)
                ->setUser($userId)
                ->setDateTime(new \DateTime())
                ->setObject('breach', $breach)
                ->setSubject('breach', []);

            $this->notificationManager->notify($notification);
        }

        $this->appConfig->setAppValue('user-' . $userId, json_encode($newData));
    }
}
