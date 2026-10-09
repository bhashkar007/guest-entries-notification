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
- **Recipients, subject and template are now set per section.** The general Email, Email Subject and Email Template settings from 2.x are no longer used, so re-enter them in the Section Settings table.
- Notifications are now sent through the queue by default. Make sure your [queue is running](https://craftcms.com/docs/5.x/system/queue.html), or turn off **Send through the queue** in the plugin settings.
- The PHP namespace is now `uxi360\guestentriesnotification`, and the service is `notifications` (was `guestEntriesNotificationService`). This only matters if you call the plugin from your own PHP code.
- Entries that Guest Entries flags as spam no longer trigger a notification.

## Settings

Go to **Settings → Plugins → Guest Entries Notification**.

### General

- **From Email** - the address notifications are sent from. Defaults to the system email address.
- **From Name** - the sender name. Defaults to the system sender name.
- **Reply-To** - where replies go. This can be an entry value: `{email}` uses the entry's `email` field, so you can reply straight to the person who submitted the entry.
- **Send through the queue** - on by default. The email is sent by a background job, so the form responds straight away and a failed email can be retried from **Utilities → Queue Manager**. Turn it off to send the email during the form submission.

### Section settings

Each section that has **Allow guest submissions** turned on in the Guest Entries plugin has its own row. Other sections aren't listed and never send notifications.

- **Notify** - turn notifications on for that section. It is off by default, so no emails are sent until you enable the sections you want.
- **Recipients** - who receives the notification. Add several addresses by separating them with commas, e.g. `editor@example.com, admin@example.com`. Defaults to the system email address.
- **Subject** - the subject line. It can include entry values, e.g. `New entry: {title}` or `{{ entry.section.name }}: {title}`. Defaults to "New Entry Created".
- **Template** - a template in your `templates/` folder for the email body, with suggestions as you type. Defaults to the built-in email.

Invalid addresses are skipped and noted in the Craft log.

### Site settings

Shown on multi-site installs. Each site can have its own **Recipients** and **Subject**, used when the section doesn't set its own.

Emails are always rendered in the language of the site the entry was submitted to.

When several settings apply, the most specific one wins: **section**, then **site**, then the default.

To check that email delivery works, use **Settings → Email → Test** in the Craft control panel.

### If Guest Entries is missing

When the Guest Entries plugin isn't installed or enabled, admins see an alert across the control panel and a warning at the top of the plugin settings, and no notifications are sent.

## Email templates

A custom template receives these variables:

| Variable  | Description                                  |
|-----------|----------------------------------------------|
| `entry`   | The submitted entry                          |
| `subject` | The rendered subject line                    |

Example:

    <h1>{{ subject }}</h1>
    <p>A new entry was submitted in {{ entry.section.name }}.</p>
    <p><strong>{{ entry.title }}</strong></p>
    <p><a href="{{ entry.cpEditUrl }}">Review the entry</a></p>

The template produces the HTML email. A plain-text version is generated from it automatically and sent alongside it.

Brought to you by [UXI360 InfoTech](https://uxi360.com)
