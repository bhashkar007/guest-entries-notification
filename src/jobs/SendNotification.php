<?php
/**
 * Guest Entries Notification plugin for Craft CMS 5.x
 *
 * @link      https://uxi360.com
 */

namespace uxi360\guestentriesnotification\jobs;

use Craft;
use craft\elements\Entry;
use craft\queue\BaseJob;
use RuntimeException;
use uxi360\guestentriesnotification\GuestEntriesNotification;

/**
 * Sends the notification email for a guest entry.
 *
 * @author    UXI360 Team
 * @since     3.0.0
 */
class SendNotification extends BaseJob
{
    public int $entryId;

    public ?int $siteId = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $entry = Entry::find()
            ->id($this->entryId)
            ->siteId($this->siteId ?? '*')
            ->status(null)
            ->one();

        // The entry was deleted before the job ran.
        if (!$entry) {
            return;
        }

        $service = GuestEntriesNotification::getInstance()->notifications;

        if (!$service->send($entry)) {
            throw new RuntimeException($service->lastError ?? 'The notification email could not be sent.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('guest-entries-notification', 'Sending guest entry notification');
    }
}
