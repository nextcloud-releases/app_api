<?php

declare(strict_types=1);

namespace OCA\AppAPI\Notifications;

use InvalidArgumentException;
use OCA\AppAPI\AppInfo\Application;
use OCA\AppAPI\Service\ExAppService;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class ExAppNotifier implements INotifier {

	public function __construct(
		private readonly IFactory      $factory,
		private readonly IURLGenerator $url,
		private readonly ExAppService  $service,
		private readonly IFactory 	   $l10nFactory
	) {
	}

	public function getID(): string {
		return Application::APP_ID;
	}

	public function getName(): string {
		return $this->factory->get(Application::APP_ID)->t('AppAPI ExApp notifier');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		$exApp = $this->service->getExApp($notification->getApp());
		if ($exApp === null) {
			throw new InvalidArgumentException();
		}
		if ($notification->getSubject() === 'ex_app_version_update' && $exApp->getEnabled()) {
			throw new InvalidArgumentException('ExApp is probably already re-enabled');
		} elseif (!$exApp->getEnabled()) { // Only enabled ExApps can render notifications
			throw new InvalidArgumentException('ExApp is disabled');
		}

		$l = $this->l10nFactory->get($notification->getApp(), $languageCode);

		$parameters = $notification->getSubjectParameters();
		if (isset($parameters['link']) && $parameters['link'] !== '') {
			$notification->setLink($parameters['link']);
		}
		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath(Application::APP_ID, 'app-dark.svg')));

		if (isset($parameters['rich_subject']) && isset($parameters['rich_subject_params'])) {
			$notification->setRichSubject($l->t($parameters['rich_subject']), $parameters['rich_subject_params']);
		}
		if (isset($parameters['rich_message']) && isset($parameters['rich_message_params'])) {
			$notification->setRichMessage($l->t($parameters['rich_message']), $parameters['rich_message_params']);
		}

		return $notification;
	}
}
