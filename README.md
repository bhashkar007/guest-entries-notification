<img src="https://github.com/bhashkar007/guest-entries-notification/blob/master/src/icon.svg" width="128">

# Guest Entries Notification plugin for Craft CMS 5.x

Sends an email notification when someone submits an entry through the [Guest Entries](https://github.com/craftcms/guest-entries) plugin.

## Requirements

- Craft CMS 5.0.0 or later
- [Guest Entries](https://github.com/craftcms/guest-entries) 4.0.0 or later
- PHP 8.2 or later

For Craft CMS 4, use `by/guest-entries-notification` 2.x.

## Installation

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Tell Composer to load the plugin:

        composer require uxi360/guest-entries-notification

3. Install the plugin, then turn on **Notify** for the sections you want in the plugin settings. Install either from **Settings → Plugins** in the control panel or with:

        php craft plugin/install guest-entries-notification

## Upgrading to Craft 5 (from `by/guest-entries-notification`)

As of 3.0.0 the package is named **`uxi360/guest-entries-notification`** (it used to be `by/guest-entries-notification`). The plugin handle is still `guest-entries-notification`, so Craft treats it as the same plugin and your saved settings are kept.

1. Update Craft and the Guest Entries plugin to their Craft 5 versions first.

2. Swap the package in your project:

        composer remove by/guest-entries-notification --no-update
        composer require uxi360/guest-entries-notification:^3.0 -W

3. Apply any pending changes:

        php craft up

Do **not** uninstall the plugin from the control panel while doing this - only the Composer package changes.

What changes for you:

- **Notifications are off until you enable them.** After upgrading, go to the plugin settings and turn on **Notify** for each section that should send emails.
- Notifications are now sent through the queue by default. Make sure your [queue is running](https://craftcms.com/docs/5.x/system/queue.html), or turn off **Send through the queue** in the plugin settings.
- The PHP namespace is now `uxi360\guestentriesnotification`, and the service is `notifications` (was `guestEntriesNotificationService`). This only matters if you call the plugin from your own PHP code.
- Entries that Guest Entries flags as spam no longer trigger a notification.

## Settings

Go to **Settings → Plugins → Guest Entries Notification**.

### Sender

- **From Email** - the address notifications are sent from. Defaults to the system email address.
- **From Name** - the sender name. Defaults to the system sender name.
- **Reply-To** - where replies go. This can be an entry value: `{email}` uses the entry's `email` field, so you can reply straight to the person who submitted the entry.

### Recipients

- **Email** - who receives the notification. Separate several addresses with commas. Defaults to the system email address.
- **CC** / **BCC** - additional recipients, comma-separated.

Invalid addresses are skipped and noted in the Craft log.

### Message

- **Email Subject** - the subject line. It can include entry values:

        New entry: {title}
        {{ entry.section.name }}: {title}

- **Email Template** - path of a template in your `templates/` folder to use for the email body. Leave blank to use the built-in template.
- **Send through the queue** - on by default. The email is sent by a background job, so the form responds straight away and a failed email can be retried from **Utilities → Queue Manager**. Turn it off to send the email during the form submission.

### Section settings

Each section that has **Allow guest submissions** turned on in the Guest Entries plugin has its own row. Other sections aren't listed and never send notifications.

- **Notify** - turn notifications on for that section. It is off by default, so no emails are sent until you enable the sections you want.
- **Recipients**, **Subject**, **Template** - override the general settings for that section. Blank cells use the general settings.

### Site settings

Shown on multi-site installs. Each site can have its own **Recipients** and **Subject**.

Emails are always rendered in the language of the site the entry was submitted to.

When several settings apply, the most specific one wins: **section**, then **site**, then the general setting.

### If Guest Entries is missing

When the Guest Entries plugin isn't installed or enabled, admins see an alert across the control panel and a warning at the top of the plugin settings, and no notifications are sent.

### Test email

**Send test email** sends a notification for the most recent entry to your own email address, using the saved settings. Save your changes before testing. The subject is prefixed with `[Test]`.

## Email templates

A custom template receives these variables:

| Variable  | Description                                  |
|-----------|----------------------------------------------|
| `entry`   | The submitted entry                          |
| `subject` | The rendered subject line                    |
| `isTest`  | `true` when the email is a test email        |

Example:

    <h1>{{ subject }}</h1>
    <p>A new entry was submitted in {{ entry.section.name }}.</p>
    <p><strong>{{ entry.title }}</strong></p>
    <p><a href="{{ entry.cpEditUrl }}">Review the entry</a></p>

The template produces the HTML email. A plain-text version is generated from it automatically and sent alongside it.

Brought to you by [UXI360 InfoTech](https://uxi360.com)
