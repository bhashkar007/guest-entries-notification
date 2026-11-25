<?php
/**
 * Guest Entries Notification plugin for Craft CMS 5.x
 *
 * @link      https://uxi360.com
 */

namespace uxi360\guestentriesnotification\services;

use Craft;
use craft\elements\Entry;
use craft\guestentries\Plugin as GuestEntries;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Queue;
use craft\mail\Message;
use craft\models\Site;
use craft\web\View;
use Throwable;
use uxi360\guestentriesnotification\GuestEntriesNotification;
use uxi360\guestentriesnotification\jobs\SendNotification;
use yii\base\Component;

/**
 * @author    UXI360 Team
 * @since     3.0.0
 */
class NotificationService extends Component
{
    private const DEFAULT_SUBJECT = 'New Entry Created';

    /**
     * @var string|null Why the last call to [[send()]] failed
     */
    public ?string $lastError = null;

    /**
     * Sends, or queues, the notification for an entry the Guest Entries plugin just saved.
     */
    public function handleSavedEntry(Entry $entry): void
    {
        if (!$this->isEnabledFor($entry)) {
            return;
        }

        if (GuestEntriesNotification::getInstance()->getSettings()->useQueue && $entry->id) {
            Queue::push(new SendNotification([
                'entryId' => $entry->id,
                'siteId' => $entry->siteId,
            ]));

            return;
        }

        // A mail problem must never break the guest's submission.
        $this->send($entry);
    }

    /**
     * Whether a notification should be sent for the entry: its section must accept guest
     * submissions in the Guest Entries plugin, and have Notify turned on here.
     */
    public function isEnabledFor(Entry $entry): bool
    {
        $section = $entry->getSection();

        if (!$section || !$this->allowsGuestSubmissions($section->uid)) {
            return false;
        }

        $overrides = $this->sectionOverrides($entry);

        return !empty($overrides['enabled']);
    }

    /**
     * Whether the Guest Entries plugin is installed and enabled.
     */
    public function isGuestEntriesAvailable(): bool
    {
        return class_exists(GuestEntries::class)
            && Craft::$app->getPlugins()->isPluginEnabled('guest-entries')
            && GuestEntries::getInstance() !== null;
    }

    /**
     * Whether the Guest Entries plugin accepts guest submissions for a section.
     */
    public function allowsGuestSubmissions(string $sectionUid): bool
    {
        if (!$this->isGuestEntriesAvailable()) {
            return false;
        }

        return (bool)GuestEntries::getInstance()->getSettings()->getSection($sectionUid)->allowGuestSubmissions;
    }

    /**
     * Sends the notification email for an entry.
     */
    public function send(Entry $entry): bool
    {
        $this->lastError = null;

        if (!$this->isEnabledFor($entry)) {
            return true;
        }

        try {
            $message = $this->inSite($entry->getSite(), fn() => $this->buildMessage($entry));

            if (!Craft::$app->getMailer()->send($message)) {
                $this->lastError = 'The mailer reported a failure. Check the Craft logs and your email settings.';
            }
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
        }

        if ($this->lastError !== null) {
            Craft::error("Couldn't send the notification for entry {$entry->id}: {$this->lastError}", __METHOD__);

            return false;
        }

        return true;
    }

    /**
     * Converts an HTML email body into a plain-text one.
     */
    public function htmlToText(string $html): string
    {
        // Things that never belong in the text version.
        $text = preg_replace('~<!--.*?-->|<(head|style|script|title)\b[^>]*>.*?</\1\s*>~is', '', $html) ?? $html;

        // Keep link targets: "label (url)".
        $text = preg_replace_callback(
            '~<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a\s*>~is',
            function(array $match) {
                $url = trim($match[2]);
                $label = trim(strip_tags($match[3]));

                if ($url === '' || str_starts_with($url, '#')) {
                    return $label;
                }

                return ($label === '' || $label === $url) ? $url : "$label ($url)";
            },
            $text
        ) ?? $text;

        // Block-level boundaries become line breaks.
        $text = preg_replace('~<br\s*/?>~i', "\n", $text) ?? $text;
        $text = preg_replace('~</(p|div|tr|table|h[1-6]|li|ul|ol|blockquote)\s*>~i', "\n\n", $text) ?? $text;
        $text = preg_replace('~<li\b[^>]*>~i', '- ', $text) ?? $text;

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        // Tidy up the whitespace left behind by the markup.
        $text = preg_replace('~[ \t]+~', ' ', $text) ?? $text;
        $text = preg_replace('~ ?\R ?~', "\n", $text) ?? $text;
        $text = preg_replace('~\n{3,}~', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Builds the message. Must be called with the entry's site as the current site.
     */
    private function buildMessage(Entry $entry): Message
    {
        $settings = GuestEntriesNotification::getInstance()->getSettings();
        $mailSettings = App::mailSettings();
        $section = $this->sectionOverrides($entry);
        $site = $settings->sites[$entry->getSite()->uid] ?? [];
        $view = Craft::$app->getView();

        $systemEmail = (string)App::parseEnv($mailSettings->fromEmail);

        // The most specific setting wins: section, then site, then the system email address.
        $recipients = $this->addresses(
            $this->firstFilled($section['emailTo'] ?? null, $site['emailTo'] ?? null),
            $entry
        );

        if (!$recipients) {
            $recipients = $this->addresses($systemEmail, $entry);
        }

        if (!$recipients) {
            throw new \RuntimeException('There is no valid recipient email address.');
        }

        $subjectTemplate = $this->firstFilled(
            $section['emailSubject'] ?? null,
            $site['emailSubject'] ?? null
        ) ?? self::DEFAULT_SUBJECT;

        $subject = trim(preg_replace('~\s+~', ' ', $this->renderString($subjectTemplate, $entry)) ?? '');

        if ($subject === '') {
            $subject = self::DEFAULT_SUBJECT;
        }

        $variables = [
            'entry' => $entry,
            'subject' => $subject,
        ];

        $template = $this->firstFilled($section['template'] ?? null);

        if ($template !== null && !$view->doesTemplateExist($template, View::TEMPLATE_MODE_SITE)) {
            Craft::warning("The email template \"$template\" doesn't exist; using the built-in email.", __METHOD__);
            $template = null;
        }

        $html = $template !== null
            ? $view->renderTemplate($template, $variables, View::TEMPLATE_MODE_SITE)
            : $this->defaultHtml($entry, $subject);

        $fromEmail = $this->firstFilled((string)App::parseEnv($settings->fromEmail), $systemEmail);
        $fromName = $this->firstFilled(
            (string)App::parseEnv($settings->fromName),
            (string)App::parseEnv($mailSettings->fromName)
        );

        $message = new Message();
        $message->setTo($recipients);
        $message->setSubject($subject);
        $message->setHtmlBody($html);
        $message->setTextBody($this->htmlToText($html));

        if ($fromEmail !== null) {
            $message->setFrom($fromName !== null ? [$fromEmail => $fromName] : $fromEmail);
        }

        if ($replyTo = $this->addresses($settings->replyTo, $entry)) {
            $message->setReplyTo($replyTo[0]);
        }

        return $message;
    }

    /**
     * Builds the built-in email, used when the section has no template.
     */
    private function defaultHtml(Entry $entry, string $subject): string
    {
        $t = fn(string $message, array $params = []) => Craft::t('guest-entries-notification', $message, $params);
        $site = $entry->getSite();
        $section = $entry->getSection();

        $rows = [
            $t('Title') => (string)$entry->title,
            $t('Section') => $section ? Craft::t('site', $section->name) : '',
            $t('Site') => Craft::t('site', $site->getName()),
            $t('Submitted') => $entry->dateCreated
                ? Craft::$app->getFormatter()->asDatetime($entry->dateCreated, 'short')
                : '',
        ];

        $tableRows = '';
        foreach ($rows as $label => $value) {
            if ($value === '') {
                continue;
            }

            $tableRows .= '<tr>'
                . '<th align="left" valign="top" style="padding:6px 16px 6px 0;color:#555;font-weight:600;">' . Html::encode($label) . '</th>'
                . '<td valign="top" style="padding:6px 0;">' . Html::encode($value) . '</td>'
                . '</tr>';
        }

        $intro = $t('A new entry was submitted to the {section} section.', [
            'section' => $section ? Craft::t('site', $section->name) : '',
        ]);

        $link = '';
        if ($url = $entry->getCpEditUrl()) {
            $link = '<p style="margin:24px 0 0;"><a href="' . Html::encode($url) . '" style="color:#0b69a3;">'
                . Html::encode($t('Review the entry'))
                . '</a></p>';
        }

        return '<!DOCTYPE html>'
            . '<html lang="' . Html::encode($site->language) . '"><head><meta charset="utf-8"><title>' . Html::encode($subject) . '</title></head>'
            . '<body style="margin:0;padding:24px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.5;color:#222;">'
            . '<h1 style="margin:0 0 16px;font-size:20px;">' . Html::encode($subject) . '</h1>'
            . '<p style="margin:0 0 16px;">' . Html::encode($intro) . '</p>'
            . '<table cellpadding="0" cellspacing="0" border="0">' . $tableRows . '</table>'
            . $link
            . '</body></html>';
    }

    /**
     * Returns the saved overrides for the entry's section.
     */
    private function sectionOverrides(Entry $entry): array
    {
        $section = $entry->getSection();

        if (!$section) {
            return [];
        }

        $overrides = GuestEntriesNotification::getInstance()->getSettings()->sections[$section->uid] ?? [];

        return is_array($overrides) ? $overrides : [];
    }

    /**
     * Runs a callback with the given site as the current site and language.
     */
    private function inSite(Site $site, callable $callback): mixed
    {
        $sites = Craft::$app->getSites();
        $view = Craft::$app->getView();
        $originalSite = $sites->getCurrentSite();
        $originalLanguage = Craft::$app->language;
        $originalTwig = null;

        if ($originalSite->id !== $site->id) {
            $sites->setCurrentSite($site);
            // A fresh Twig environment, so globals are loaded for this site.
            $originalTwig = $view->getTwig();
            $view->setTwig($view->createTwig());
        }

        Craft::$app->language = $site->language;

        try {
            return $callback();
        } finally {
            if ($originalTwig !== null) {
                $sites->setCurrentSite($originalSite);
                $view->setTwig($originalTwig);
            }

            Craft::$app->language = $originalLanguage;
        }
    }

    /**
     * Renders a setting that may reference entry values, e.g. `New entry: {title}`.
     */
    private function renderString(string $template, Entry $entry): string
    {
        if (!str_contains($template, '{')) {
            return $template;
        }

        return Craft::$app->getView()->renderObjectTemplate($template, $entry, ['entry' => $entry]);
    }

    /**
     * Turns a comma-separated setting into a list of valid email addresses.
     *
     * @return string[]
     */
    private function addresses(?string $list, Entry $entry): array
    {
        if ($list === null || trim($list) === '') {
            return [];
        }

        try {
            $list = $this->renderString($list, $entry);
        } catch (Throwable $e) {
            Craft::warning("Couldn't render the address setting \"$list\": {$e->getMessage()}", __METHOD__);

            return [];
        }

        $addresses = [];

        foreach (preg_split('~[,;\s]+~', $list, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $address) {
            $address = (string)App::parseEnv($address);

            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                $addresses[] = $address;
            } else {
                Craft::warning("Skipping the invalid email address \"$address\".", __METHOD__);
            }
        }

        return array_values(array_unique($addresses));
    }

    /**
     * Returns the first value that isn't empty, trimmed, or null.
     */
    private function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
