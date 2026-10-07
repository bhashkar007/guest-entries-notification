<?php
/**
 * Guest Entries Notification plugin for Craft CMS 5.x
 *
 * @link      https://uxi360.com
 */

namespace uxi360\guestentriesnotification\models;

use craft\base\Model;

/**
 * @author    UXI360 Team
 * @since     2.0.0
 */
class Settings extends Model
{
    /**
     * @var string|null Sender email. Defaults to the system email address.
     */
    public ?string $fromEmail = null;

    /**
     * @var string|null Sender name. Defaults to the system sender name.
     */
    public ?string $fromName = null;

    /**
     * @var string|null Comma-separated recipients. Defaults to the system email address.
     */
    public ?string $emailTo = null;

    /**
     * @var string|null Comma-separated CC recipients.
     */
    public ?string $cc = null;

    /**
     * @var string|null Comma-separated BCC recipients.
     */
    public ?string $bcc = null;

    /**
     * @var string|null Reply-To address. May reference an entry value, e.g. `{email}`.
     */
    public ?string $replyTo = null;

    /**
     * @var string|null Subject. May reference entry values, e.g. `New entry: {title}`.
     */
    public ?string $emailSubject = 'New Entry Created';

    /**
     * @var string|null Path of a site template to use for the email body.
     */
    public ?string $confirmationTemplate = null;

    /**
     * @var bool Whether notifications are sent by a queue job rather than during the request.
     */
    public bool $useQueue = true;

    /**
     * @var array Per-section overrides, indexed by section UID.
     *            Each may have `enabled`, `emailTo`, `emailSubject` and `template`.
     */
    public array $sections = [];

    /**
     * @var array Per-site overrides, indexed by site UID.
     *            Each may have `emailTo` and `emailSubject`.
     */
    public array $sites = [];

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            // An empty table is posted as an empty string.
            foreach (['sections', 'sites'] as $name) {
                if (array_key_exists($name, $values) && !is_array($values[$name])) {
                    $values[$name] = [];
                }
            }

            if (array_key_exists('useQueue', $values)) {
                $values['useQueue'] = (bool)$values['useQueue'];
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['fromEmail', 'fromName', 'emailTo', 'cc', 'bcc', 'replyTo', 'emailSubject', 'confirmationTemplate'], 'trim'],
            [['fromEmail', 'fromName', 'emailTo', 'cc', 'bcc', 'replyTo', 'emailSubject', 'confirmationTemplate'], 'string'],
            [['fromEmail'], 'email', 'when' => fn(self $model) => !str_starts_with((string)$model->fromEmail, '$')],
            [['useQueue'], 'boolean'],
            [['emailSubject'], 'default', 'value' => 'New Entry Created'],
        ];
    }
}
