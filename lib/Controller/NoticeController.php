<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Controller;

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCP\IRequest;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;
use OCP\Mail\IMailer;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Notice;
use OCA\Polls\Db\NoticeMapper;

class NoticeController extends Controller {

	private $userId;
	private $mapper;
	private $logger;

	private $config;
	private $urlGenerator;
	private $trans;
	private $transFactory;
	private $mailer;

	/**
	 * NoticeController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param NoticeMapper $mapper
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $trans
	 * @param IFactory $transFactory
	 * @param IMailer $mailer
	 */

	public function __construct(
		string $appName,
		$UserId,
		NoticeMapper $mapper,
		IRequest $request,
		ILogger $logger,
		IConfig $config,
		IURLGenerator $urlGenerator,
		IL10N $trans,
		IFactory $transFactory,
		IMailer $mailer

	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->logger = $logger;

		$this->config = $config;
		$this->trans = $trans;
		$this->transFactory = $transFactory;
		$this->urlGenerator = $urlGenerator;
		$this->mailer = $mailer;

	}

	/**
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {

		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->mapper->findByUserAndPoll($pollId, $this->userId);
		} catch (MultipleObjectsReturnedException $e) {
			// should not happen, but who knows
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		return new DataResponse(null, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @param integer $pollId
	 */
	public function set(Notice $notice) {
		$notice->setPollId($pollId);
		$notice->setMessage($message);
		return true;
	}

	public function logEvent($pollId, $messageId)

	// /**
	//  * @param int $pollId
	//  * @param string $from
	//  */
	// private function sendNotifications($pollId, $from) {
	// 	$poll = $this->eventMapper->find($pollId);
	// 	$notifications = $this->mapper->findAllByPoll($pollId);
	// 	foreach ($notifications as $notification) {
	// 		if ($from === $notification->getUserId()) {
	// 			continue;
	// 		}
	// 		$recUser = $this->userMgr->get($notification->getUserId());
	// 		if (!$recUser instanceof IUser) {
	// 			continue;
	// 		}
	// 		$email = \OC::$server->getConfig()->getUserValue($notification->getUserId(), 'settings', 'email');
	// 		if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	// 			continue;
	// 		}
	// 		$url = $this->urlGenerator->getAbsoluteURL(
	// 			$this->urlGenerator->linkToRoute('polls.page.vote',
	// 				array('hash' => $poll->getHash()))
	// 		);
	//
	// 		$sendUser = $this->userMgr->get($from);
	// 		$sender = $from;
	// 		if ($sendUser instanceof IUser) {
	// 			$sender = $sendUser->getDisplayName();
	// 		}
	//
	// 		$lang = $this->config->getUserValue($notification->getUserId(), 'core', 'lang');
	// 		$trans = $this->transFactory->get('polls', $lang);
	// 		$emailTemplate = $this->mailer->createEMailTemplate('polls.Notification', [
	// 			'user' => $sender,
	// 			'title' => $poll->getTitle(),
	// 			'link' => $url,
	// 		]);
	// 		$emailTemplate->setSubject($trans->t('Polls App - New Activity'));
	// 		$emailTemplate->addHeader();
	// 		$emailTemplate->addHeading($trans->t('Polls App - New Activity'), false);
	//
	// 		$emailTemplate->addBodyText(str_replace(
	// 			['{user}', '{title}'],
	// 			[$sender, $poll->getTitle()],
	// 			$trans->t('{user} participated in the poll "{title}"')
	// 		));
	//
	// 		$emailTemplate->addBodyButton(
	// 			htmlspecialchars($trans->t('Go to poll')),
	// 			$url,
	// 			/** @scrutinizer ignore-type */ false
	// 		);
	//
	// 		$emailTemplate->addFooter();
	// 		try {
	// 			$message = $this->mailer->createMessage();
	// 			$message->setTo([$email => $recUser->getDisplayName()]);
	// 			$message->useTemplate($emailTemplate);
	// 			$this->mailer->send($message);
	// 		} catch (\Exception $e) {
	// 			$this->logger->logException($e, ['app' => 'polls']);
	// 		}
	// 	}
	// }

	/**
	 * @param string $token
	 */
	public function sendInvitationMail($token) {
		$recipients = [];
		$share = $this->shareMapper->findByToken($token);
		$event = $this->eventMapper->find($share->getPollId());
		$contactsManager = \OC::$server->getContactsManager();

		if ($share->getType() === 'user') {

			$recipients[] = array(
				'userId' => $share->getUserId(),
				'displayName' => $this->userMgr->get($share->getUserId())->getDisplayName(),
				'language' => $this->config->getUserValue($share->getUserId(), 'core', 'lang'),
				'eMail' => $this->userMgr->get($share->getUserId())->getEMailAddress(),
				'link' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('polls.page.vote_poll', array('pollId' => $share->getpollId())))
			);

		} elseif ($share->getType() === 'contact') {
			$contacts = $contactsManager->search($share->getUserId(), array('UID'));
			if (is_array($contacts)) {
				$contact = $contacts[0];

				$recipients[] = array(
					'userId' => $share->getUserId(),
					'displayName' => $contact['FN'],
					'language' => $this->config->getUserValue($event->getOwner(), 'core', 'lang'),
					'eMail' => $contact['EMAIL'][0],
					'link' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('polls.page.vote_poll', array('pollId' => $share->getpollId())))
				);
			} else {
				return;
			}

		} elseif ($share->getType() === 'external' || $share->getType() === 'mail') {

			$recipients[] = array(
				'userId' => $share->getUserId(),
				'displayName' => $share->getUserId(),
				'language' => $this->config->getUserValue($event->getOwner(), 'core', 'lang'),
				'eMail' => $share->getUserEmail(),
				'link' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('polls.page.vote_public', array('token' => $share->getToken())))
			);

		} elseif ($share->getType() === 'group') {

			$groupMembers = array_keys($this->groupMgr->displayNamesInGroup($share->getUserId()));

			foreach ($groupMembers as $member) {
				if ($event->getOwner() === $member) {
					continue;
				}

				$recipients[] = array(
					'userId' => $member,
					'displayName' => $this->userMgr->get($member)->getDisplayName(),
					'language' => $this->config->getUserValue($share->getUserId(), 'core', 'lang'),
					'eMail' => $this->userMgr->get($member)->getEMailAddress(),
					'link' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('polls.page.vote_poll', array('pollId' => $share->getpollId())))
				);

			}
		}

		$sendUser = $this->userMgr->get($event->getOwner());
		$sender = $event->getOwner();
		if ($sendUser instanceof IUser) {
			$sender = $sendUser->getDisplayName();
		}

		foreach ($recipients as $recipient) {

			if ($recipient['eMail'] === null || !filter_var($recipient['eMail'], FILTER_VALIDATE_EMAIL)) {
				continue;
			}

			$trans = $this->transFactory->get('polls', $recipient['language']);

			$emailTemplate = $this->mailer->createEMailTemplate('polls.Invitation', [
				'user' => $sender,
				'title' => $event->getTitle(),
				'link' => $recipient['link']
			]);

			$emailTemplate->setSubject($trans->t('Poll invitation "%s"', $event->getTitle()));
			$emailTemplate->addHeader();
			$emailTemplate->addHeading($trans->t('Poll invitation "%s"', $event->getTitle()), false);

			$emailTemplate->addBodyText(str_replace(
				['{user}', '{title}'],
				[$sender, $event->getTitle()],
				$trans->t('{user} invited you to take part in the poll "{title}"' )
			));

				$emailTemplate->addBodyButton(
					htmlspecialchars($trans->t('Go to poll')),
					$recipient['link']
				);

			$emailTemplate->addFooter();

			try {

				$message = $this->mailer->createMessage();
				$message->setTo([$recipient['eMail'] => $recipient['displayName']]);
				$message->useTemplate($emailTemplate);
				$this->mailer->send($message);

			} catch (\Exception $e) {

				$this->logger->logException($e, ['app' => 'polls']);

			}
		}
	}

}
