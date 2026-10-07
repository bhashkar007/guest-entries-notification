<?php
/**
 * Guest Entries Notification plugin for Craft CMS 5.x
 *
 * @link      https://uxi360.com
 */

namespace uxi360\guestentriesnotification;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterCpAlertsEvent;
use craft\guestentries\controllers\SaveController;
use craft\guestentries\events\SaveEvent;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\Html;
use uxi360\guestentriesnotification\models\Settings;
use uxi360\guestentriesnotification\services\NotificationService;
use yii\base\Event;

/**
 * @author    UXI360 Team
 * @since     3.0.0
 *
 * @property-read NotificationService $notifications
 * @method Settings getSettings()
 */
class GuestEntriesNotification extends Plugin
{
    /**
     * @inheritdoc
     */
    public string $schemaVersion = '2.0.0';

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public static function config(): array
    {
        return [
            'components' => [
                'notifications' => NotificationService::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(Cp::class, Cp::EVENT_REGISTER_ALERTS, function(RegisterCpAlertsEvent $event) {
                $user = Craft::$app->getUser()->getIdentity();

                if ($user && $user->admin && !$this->notifications->isGuestEntriesAvailable()) {
                    $event->alerts[] = Craft::t('guest-entries-notification', 'Guest Entries Notification needs the Guest Entries plugin, which isn’t installed or enabled. No notifications will be sent.');
                }
            });
        }

        // Nothing to listen to if the Guest Entries plugin isn't there.
        if (!class_exists(SaveController::class)) {
            return;
        }

        Event::on(
            SaveController::class,
            SaveController::EVENT_AFTER_SAVE_ENTRY,
            function(SaveEvent $event) {
                if ($event->isSpam || !$event->entry) {
                    return;
                }

                $this->notifications->handleSavedEntry($event->entry);
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        $settings = $this->getSettings();

        $sectionRows = [];
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            // Only sections that accept guest submissions can send notifications.
            if (!$this->notifications->allowsGuestSubmissions($section->uid)) {
                continue;
            }

            $saved = $settings->sections[$section->uid] ?? [];
            $sectionRows[$section->uid] = [
                'heading' => Html::encode(Craft::t('site', $section->name)),
                'enabled' => !empty($saved['enabled']),
                'emailTo' => $saved['emailTo'] ?? '',
                'emailSubject' => $saved['emailSubject'] ?? '',
                'template' => $saved['template'] ?? '',
            ];
        }

        $siteRows = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $saved = $settings->sites[$site->uid] ?? [];
            $siteRows[$site->uid] = [
                'heading' => Html::encode(Craft::t('site', $site->getName())),
                'emailTo' => $saved['emailTo'] ?? '',
                'emailSubject' => $saved['emailSubject'] ?? '',
            ];
        }

        $mailSettings = App::mailSettings();

        return Craft::$app->getView()->renderTemplate('guest-entries-notification/settings', [
            'settings' => $settings,
            'sectionRows' => $sectionRows,
            'siteRows' => $siteRows,
            'guestEntriesAvailable' => $this->notifications->isGuestEntriesAvailable(),
            'isMultiSite' => Craft::$app->getIsMultiSite(),
            'systemFromEmail' => App::parseEnv($mailSettings->fromEmail),
            'systemFromName' => App::parseEnv($mailSettings->fromName),
        ]);
    }
}
