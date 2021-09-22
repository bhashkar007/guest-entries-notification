<img src="https://github.com/bhashkar007/guest-entries-notification/blob/master/src/icon.svg" width="128">

# Guest Entries Notification Overview

This plugin sends a notification when a guest creates an entry through guest-entry plugin.

## Requirements

This plugin requires Craft CMS 3 and the [Guest Entries](https://github.com/craftcms/guest-entries) plugin.

## Installation

Install this plugin through the Plugin Store or follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require by/guest-entries-notification

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Craft Contact Form Extensions.

## Configuring Guest Entries Notification

Guest Entries Notification have following setting fields:
- **From Email**

    Used to set Sender Email. By default **System Email Address** will be used.
    
- **From Name**

    Used to set Sender Email. By default **Sender Name** will be used.
    
- **Email**

    Used to set Recevier Email. By default **System Email Address** will be used.
    
- **Email Subject**

    Used to set Email Subject.
    
- **Set the template for the email**

    Used to override Email template. The template will receive a `entry` variable through which all entry data can be used. By default template from [templates/notification](/src/templates/notification.twig) will be used.

Brought to you by [Bhashkar Yadav](https://360adaptive.com)
