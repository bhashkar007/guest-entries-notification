<?php
/**
 * Guest Entries Notification plugin for Craft CMS 3.x
 *
 * A plugin to get notification when an entry is created.
 *
 * @link      https://360adaptive.com
 * @copyright Copyright (c) 2018 Bhashkar Yadav
 */

namespace by\guestentriesnotification\assetbundles\GuestEntriesNotification;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Bhashkar Yadav
 * @package   GuestEntriesNotification
 * @since     1.0.0
 */
class GuestEntriesNotificationAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@by/guestentriesnotification/assetbundles/guestentriesnotification/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/GuestEntriesNotification.js',
        ];

        $this->css = [
            'css/GuestEntriesNotification.css',
        ];

        parent::init();
    }
}
