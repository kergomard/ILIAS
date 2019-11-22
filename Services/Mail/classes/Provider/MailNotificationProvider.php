<?php declare(strict_types=1);

namespace ILIAS\Mail\Provider;

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\Notification\Provider\AbstractNotificationProvider;
use ILIAS\GlobalScreen\Scope\Notification\Provider\NotificationProvider;

/**
 * Class MailNotificationProvider
 * @author Michael Jansen <mjansen@databay.de>
 */
class MailNotificationProvider extends AbstractNotificationProvider implements NotificationProvider
{
    const MUTED_UNTIL_PREFERENCE_KEY = 'mail_nc_muted_until';

    /**
     * @inheritDoc
     */
    public function getNotifications(): array
    {
        $id = function (string $id): IdentificationInterface {
            return $this->if->identifier($id);
        };

        if (0 === (int)$this->dic->user()->getId() || $this->dic->user()->isAnonymous()) {
            return [];
        }

        $hasInternalMailAccess = $this->dic->rbac()->system()->checkAccess(
            'internal_mail', \ilMailGlobalServices::getMailObjectRefId()
        );
        if (!$hasInternalMailAccess) {
            return [];
        }

        $leftIntervalTimestamp = $this->dic->user()->getPref(self::MUTED_UNTIL_PREFERENCE_KEY);
        $numberOfNewMessages = \ilMailGlobalServices::getNumberOfNewMailsByUserId(
            (int) $this->dic->user()->getId(),
            is_numeric($leftIntervalTimestamp) ? (int) $leftIntervalTimestamp : 0
        );
        if (0 === $numberOfNewMessages) {
            return [];
        }

        $this->dic->language()->loadLanguageModule('mail');

        $factory = $this->globalScreen()->notifications()->factory();

        $mailUrl = 'ilias.php?baseClass=ilMailGUI';

        if (1 === $numberOfNewMessages) {
            $linkText = $this->dic->language()->txt('nc_mail_unread_messages_number_s');
        } else {
            $linkText = sprintf(
                $this->dic->language()->txt('nc_mail_unread_messages_number_p'),
                $numberOfNewMessages
            );
        }

        $body = sprintf(
            $this->dic->language()->txt('nc_mail_unread_messages'),
            $this->dic->ui()->renderer()->render($this->dic->ui()->factory()
                ->link()
                ->standard($linkText, $mailUrl)
                ->withOpenInNewViewport(true)
            )
        );

        $icon = $this->dic->ui()->factory()->symbol()->icon()->standard('mail', 'mail');
        $title = $this->dic->ui()->factory()->link()->standard(
            $this->dic->language()->txt('nc_mail_noti_item_title'),
            $mailUrl
        );

        $notificationItem  = $this->dic->ui()->factory()
            ->item()
            ->notification($title, $icon)
            ->withDescription($body)
            ->withProperties([]);

        $group = $factory->standardGroup($id('mail_bucket_group'))
            ->withTitle($this->dic->language()->txt('mail'))
            ->addNotification(
                $factory->standard($id('mail_bucket'))
                    ->withNotificationItem($notificationItem )
                    ->withClosedCallable(
                        function () {
                            $this->dic->user()->writePref(self::MUTED_UNTIL_PREFERENCE_KEY, time());
                        })
                    ->withNewAmount(1)
            );

        return [
            $group,
        ];
    }
}
