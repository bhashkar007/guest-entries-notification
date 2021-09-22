<?php
/**
 * Guest Entries Notification plugin for Craft CMS 3.x
 *
 * A plugin to get notification when an entry is created.
 *
 * @link      https://360adaptive.com
 * @copyright Copyright (c) 2018 Bhashkar Yadav
 */

namespace by\guestentriesnotification\services;

use by\guestentriesnotification\GuestEntriesNotification;

use Craft;
use craft\base\Component;
use craft\web\View;
use craft\mail\Mailer;
use craft\mail\Message;

/**
 * @author    Bhashkar Yadav
 * @package   GuestEntriesNotification
 * @since     1.0.0
 */
class GuestEntriesNotificationService extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function sendNotification($entry)
    {
        $settings = Craft::$app->systemSettings->getSettings('email');
        $pluginSettings = GuestEntriesNotification::$plugin->getSettings();
        
        if(isset($pluginSettings->emailTo) and !empty($pluginSettings->emailTo)){
            $emailTo = explode(',', $pluginSettings->emailTo);
        }else{
            $emailTo = $settings['fromEmail'];
        }
        
        if(isset($pluginSettings->fromEmail) and !empty($pluginSettings->fromEmail)){
            $fromEmail = $pluginSettings->fromEmail;
        }else{
            $fromEmail = $settings['fromEmail'];
        }
        
        if(isset($pluginSettings->fromName) and !empty($pluginSettings->fromName)){
            $fromName = $pluginSettings->fromName;
        }else{
            $fromName = $settings['fromName'];
        }
        
        if(isset($pluginSettings->emailSubject) and !empty($pluginSettings->emailSubject)){
            $subject = $pluginSettings->emailSubject;
        }else{
            $subject = 'New Entry Created';
        }
        
        if(isset($pluginSettings->confirmationTemplate) and !empty($pluginSettings->confirmationTemplate)){
            Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
            $body = Craft::$app->view->renderTemplate(
                    $pluginSettings->confirmationTemplate,
                    ['entry' => $entry]
                );
        }else{
            Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
            $body = Craft::$app->getView()->renderTemplate(
                'guest-entries-notification/notification.twig', 
                ['entry' => $entry]
            );
        }

        $message = new Message();
        $message->setFrom([$fromEmail => $fromName]);
        $message->setTo($emailTo);
        $message->setSubject($subject);
        $message->setHtmlBody($body);

        Craft::$app->mailer->send($message);
    }
}
