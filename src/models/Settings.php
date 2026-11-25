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
     * Settings that no longer exist, but may still be stored by older versions.
     */
    private const LEGACY_SETTINGS = ['emailTo', 'cc', 'bcc', 'emailSubject', 'confirmationTemplate'];

    /**
     * @var string|null Sender email. Defaults to the system email address.
     */
    public ?string $fromEmail = null;

    /**
     * @var string|null Sender name. Defaults to the system sender name.
     */
    public ?string $fromName = null;

    /**
     * @var string|null Reply-To address. May reference an entry value, e.g. `{email}`.
     */
    public ?string $replyTo = null;

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
            // Settings from earlier versions that are now set per section.
            foreach (self::LEGACY_SETTINGS as $name) {
                unset($values[$name]);
            }

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
            [['fromEmail', 'fromName', 'replyTo'], 'trim'],
            [['fromEmail', 'fromName', 'replyTo'], 'string'],
            [['fromEmail'], 'email', 'when' => fn(self $model) => !str_starts_with((string)$model->fromEmail, '$')],
            [['useQueue'], 'boolean'],
        ];
    }
}
