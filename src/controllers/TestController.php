<?php
/**
 * Guest Entries Notification plugin for Craft CMS 5.x
 *
 * @link      https://uxi360.com
 */

namespace uxi360\guestentriesnotification\controllers;

use Craft;
use craft\web\Controller;
use uxi360\guestentriesnotification\GuestEntriesNotification;
use yii\web\Response;

/**
 * @author    UXI360 Team
 * @since     3.0.0
 */
class TestController extends Controller
{
    /**
     * Sends a test notification, built from the most recent entry, to the logged-in user.
     */
    public function actionSend(): Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $service = GuestEntriesNotification::getInstance()->notifications;
        $user = Craft::$app->getUser()->getIdentity();
        $entry = $service->findSampleEntry();

        if (!$entry) {
            $this->setFailFlash(Craft::t('guest-entries-notification', 'A test email needs at least one entry to exist.'));
        } elseif ($service->send($entry, [$user->email], true)) {
            $this->setSuccessFlash(Craft::t('guest-entries-notification', 'Test email sent to {email}.', [
                'email' => $user->email,
            ]));
        } else {
            $this->setFailFlash(Craft::t('guest-entries-notification', 'Couldn’t send the test email: {error}', [
                'error' => $service->lastError ?? Craft::t('guest-entries-notification', 'check your email settings.'),
            ]));
        }

        return $this->redirectToPostedUrl();
    }
}
