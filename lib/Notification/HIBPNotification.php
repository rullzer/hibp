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

namespace OCA\HIBP\Notification;

use OCA\HIBP\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class HIBPNotification implements INotifier {

	/** @var IFactory */
	private $l10nFactory;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator) {
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
	}

	public function getID(): string {
		return Application::APP_NAME;
	}

	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_NAME)->t('Have I Been Pwned');
	}

	private function parseBreach(INotification $notification, IL10N $l): INotification {
		$notification->setParsedSubject($l->t('Your e-mail appeared in a data breach!'));
		$notification->setRichSubject($l->t('Your e-mail appeared in a data breach!'));
		$notification->setParsedMessage(
			$l->t('Your e-mail address appeared in the %1$s breach. Please visit haveibeenpwned.com for more information.', [$notification->getObjectId()])
		);
		$notification->setRichMessage(
			$l->t('Your e-mail address appeared in the %1$s breach. Please visit haveibeenpwned.com for more information.', [$notification->getObjectId()])
		);
		$notification->setLink('https://haveibeenpwned.com');

		return $notification;
	}

	private function parseMissingKey(INotification $notification, IL10N $l): INotification {
		$notification->setParsedSubject($l->t('Your HIBP API key is not set.'));
		$notification->setRichSubject($l->t('Your HIBP API key is not set'));
		$notification->setParsedMessage(
			$l->t('Your HIBP API key is not set. Get your key at https://haveibeenpwned.com/API/Key and set it via "occ hibp:set-api-key".')
		);
		$notification->setRichMessage(
			$l->t('Your HIBP API key is not set. Get your key at https://haveibeenpwned.com/API/Key and set it via "occ hibp:set-api-key".')
		);
		$notification->setLink('https://haveibeenpwned.com/API/Key');

		return $notification;
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_NAME) {
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get(Application::APP_NAME, $languageCode);

		if ($notification->getObjectType() === 'breach') {
			return $this->parseBreach($notification, $l);
		}
		if ($notification->getObjectType() === 'key' && $notification->getObjectId() === 'missing') {
			return $this->parseMissingKey($notification, $l);
		}

		throw new \InvalidArgumentException();
	}
}
