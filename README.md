# WordPress Comment Anti-Spam

A lightweight WordPress anti-spam plugin that protects blog comment forms from spam using a honeypot field, timing checks, rate limiting, link limits, keyword filtering, and pingback blocking.

This plugin is designed as a simple CAPTCHA-free solution for WordPress websites that receive spam through blog comment forms.

## Features

- Adds a hidden honeypot field to the WordPress comment form
- Blocks bots that submit the form too quickly
- Expires old comment form submissions
- Limits how often the same IP address can submit comments
- Blocks comments with too many links
- Filters common spam keywords
- Removes the default WordPress “Website” field from comments
- Blocks pingbacks and trackbacks
- Works with standard WordPress themes and page builders
- Lightweight and dependency-free
- No external API required
- No CAPTCHA required

## Why This Plugin?

Many WordPress comment spam solutions rely on third-party services or CAPTCHA tools. This plugin provides a privacy-friendly and lightweight alternative.

It is useful for small nonprofit, church, education, foundation, and community websites that want simple spam protection without adding friction for real visitors.

## Installation

### Option 1: Install as a normal WordPress plugin

1. Download or clone this repository.
2. Upload the plugin folder to:

   ```text
   /wp-content/plugins/
3. The final structure should look like this:
   /wp-content/plugins/wordpress-comment-anti-spam/
└── wordpress-comment-anti-spam.php
4. Go to your WordPress admin dashboard.
5. Navigate to:
   Plugins → Installed Plugins
6. Find Wordpress Comment Anti-Spam
7. Click Activate

### Option 2: Install as Must-Use Plugin
You may also install this as a must-use plugin so that it loads automatically.
1. Upload the PHP file directly to:
   /wp-content/mu-plugins/
2. The final structure should look like this:
   /wp-content/mu-plugins/wordpress-comment-anti-spam.php
3. No activation is required. WordPress loads must-use plugins automatically.
   Note: If the mu-plugins folder does not exist, you can create it.
   
## Requirements
WordPress 5.8 or newer
PHP 7.4 or newer
A theme that uses the standard WordPress comment form hooks
Recommended PHP Version

PHP 8.0 or newer is recommended.

## How It Works

The plugin uses several lightweight checks to detect and block spam comments.

1. Honeypot Field

A hidden field is added to the comment form. Human users do not see it, but many spam bots fill it in automatically. If the field contains a value, the comment is blocked.

2. Timing Check

The plugin records when the comment form is loaded. If a comment is submitted too quickly, it is likely automated spam and is blocked.

3. Form Expiration

Very old form submissions are rejected. This helps reduce replay-style spam submissions.

4. Rate Limiting

The plugin limits how frequently the same IP address can submit comments.

5. Link Limit

Spam comments often contain multiple links. The plugin blocks comments that exceed the configured link limit.

6. Keyword Filtering

The plugin checks comment content for common spam keywords and blocks matching comments.

7. Pingback and Trackback Blocking

Pingbacks and trackbacks are commonly abused for spam. This plugin disables them.

## Configuration
The plugin is intentionally simple and does not include a settings page.
You can adjust the protection rules directly inside the PHP file.

## Privacy
This plugin does not send visitor data to any third-party service.
It uses the visitor IP address only for temporary rate limiting through WordPress transients.
No external API calls are made.

## Security Notes
This plugin includes:

WordPress nonce validation
Honeypot validation
Timestamp validation
Conservative IP handling
Pingback blocking
Comment preprocessing before database insertion

It is not a complete replacement for broader WordPress security practices. You should still keep WordPress core, themes, and plugins updated.

## Compatibility
Tested with:
WordPress blog comment forms
Avada theme comment forms
Standard WordPress comment hooks
This plugin should work with most themes that use the default WordPress comment form system.

## Recommended WordPress Settings
For stronger protection, also review these WordPress settings:
Settings → Discussion
Recommended options:

Require comment author name and email
Hold comments with multiple links for moderation
Manually approve first-time commenters
Disable pingbacks and trackbacks
Enable comment moderation where appropriate

## Roadmap

Possible future improvements:

Admin settings page
Custom banned keyword editor
Spam log dashboard
Per-post enable/disable option
Optional allowlist for trusted commenters
Multisite support improvements
Translation-ready language files

## Contributing
Contributions are welcome.
To contribute:
Fork the repository.
Create a feature branch.
Make your changes.
Test on a WordPress installation.
Submit a pull request.

## License
This project is licensed under the GPLv2 or later.
WordPress plugins should use a GPL-compatible license.

## Author
Created by Oludotun Babayemi and reusable for all other WordPress websites.

## Disclaimer
This plugin helps reduce spam but cannot guarantee that all spam will be blocked. For high-traffic or heavily targeted websites, consider combining it with moderation rules, server-level protection, or a trusted anti-spam service.

