<?php
/**
 * Guest Entries Notification plugin for Craft CMS 4.x
 *
 * A plugin to get notification when an entry is created.
 *
 * @link      https://360adaptive.com
 * @copyright Copyright (c) 2018 Bhashkar Yadav
 */

namespace by\guestentriesnotification;

use by\guestentriesnotification\services\GuestEntriesNotificationService as GuestEntriesNotificationServiceService;
use by\guestentriesnotification\models\Settings;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use craft\guestentries\controllers\SaveController;
use craft\guestentries\events\SaveEvent;

use yii\base\Event;

/**
 * Class GuestEntriesNotification
 *
 * @author    Bhashkar Yadav
 * @package   GuestEntriesNotification
 * @since     2.0.0
 *
 * @property  GuestEntriesNotificationServiceService $guestEntriesNotificationService
 */
class GuestEntriesNotification extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var GuestEntriesNotification
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '2.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;


        if (!Craft::$app->plugins->isPluginInstalled('guest-entries')) {
            Craft::$app->session->setNotice(Craft::t('guest-entries-notification', 'The Guest Entries plugin is not installed or activated, Guest Entries Notification does not work without it.'));
        }


        Event::on(SaveController::class, SaveController::EVENT_AFTER_SAVE_ENTRY, function(SaveEvent $e) {
            $entry = $e->entry;
            GuestEntriesNotification::$plugin->guestEntriesNotificationService->sendNotification($entry);
        });

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'guest-entries-notification',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

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
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'guest-entries-notification/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
