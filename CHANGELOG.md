# Guest Entries Notification Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 3.0.0 - 2026-10-07

> The package has been renamed from `by/guest-entries-notification` to `uxi360/guest-entries-notification`. See the README for upgrade instructions.

### Added
- Craft CMS 5 and Guest Entries 4 compatibility.
- Per-section settings: choose which sections send notifications, and give a section its own recipients, subject and template.
- Per-site recipients and subject, and emails are rendered in the language of the site the entry was submitted to.
- The subject can include entry values, e.g. `New entry: {title}`.
- Reply-To, CC and BCC settings. Reply-To can come from an entry field, e.g. `{email}`.
- Notifications are sent through the queue by default, with a setting to send them immediately instead.
- A plain-text version of the email is generated and sent alongside the HTML.
- A “Send test email” button on the settings page.
- A control panel alert, and a warning on the settings page, when the Guest Entries plugin isn’t installed or enabled.
- Email templates now also receive `subject` and `isTest` variables.

### Changed
- The package is now `uxi360/guest-entries-notification` and the PHP namespace is `uxi360\guestentriesnotification`. The plugin handle is still `guest-entries-notification`, and existing settings are kept.
- The plugin now requires Craft CMS 5.0.0, Guest Entries 4.0.0 and PHP 8.2 or later.
- The plugin's service is now available as `notifications` (was `guestEntriesNotificationService`).
- Notifications are now off by default and must be turned on for each section in the plugin settings.
- Notifications are only sent for sections that allow guest submissions in the Guest Entries plugin.
- Entries flagged as spam by Guest Entries no longer trigger a notification.
- The plugin is now maintained by UXI360 InfoTech.

### Removed
- Removed the unused asset bundle, empty config file and translation file.

### Fixed
- Fixed a bug where the template mode wasn't restored after the email was rendered, which could break the page shown after a submission.
- Fixed a bug where a system email address set with an environment variable wasn't resolved.
- Recipient addresses are now trimmed and validated.
- A mail failure no longer interrupts the guest's submission; it is logged instead.
- Fixed an error in console requests when the Guest Entries plugin wasn't installed.

## 2.0.0
### Changed
- Craft CMS 4 compatibility.

### Fixed
- Fixed the From Name setting.

## 1.0.0 - 2018-07-11
### Added
- Initial release
