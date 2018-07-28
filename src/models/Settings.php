<?php
/**
 * Guest Entries Notification plugin for Craft CMS 3.x
 *
 * A plugin to get notification when an entry is created.
 *
 * @link      http://sidd3.com
 * @copyright Copyright (c) 2018 Bhashkar Yadav
 */

namespace by\guestentriesnotification\models;

use by\guestentriesnotification\GuestEntriesNotification;

use Craft;
use craft\base\Model;

/**
 * @author    Bhashkar Yadav
 * @package   GuestEntriesNotification
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $fromEmail = '';
    public $fromName = '';
    public $emailTo = '';
    public $emailSubject = 'New Entry Created';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['fromEmail', 'string'],
            ['fromEmail', 'default', 'value' => ''],
            ['fromName', 'string'],
            ['fromName', 'default', 'value' => ''],
            ['emailTo', 'string'],
            ['emailTo', 'default', 'value' => ''],
            ['emailSubject', 'string'],
            ['emailSubject', 'default', 'value' => 'New Entry Created'],
        ];
    }
}
