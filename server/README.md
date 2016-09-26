# Setup

 * `cp config/config.php.dist config/config.php` and personnalize settings (calendars IDs can be found in Google Calendars setting tab).
 * Create an OAuth 2.0 client ID in [Google developer console](https://console.developers.google.com/). Choose the "Other" type. [More information here](https://developers.google.com/google-apps/calendar/auth).
 * Download the credentials JSON and put them in `credentials/calendar_credentials.json`.
 * Run `php scripts/get_token.php` to get the access token and follow instructions (this operation needs you to be able to copy paste text between CI and an internet browser).

# Run

```
php -S YOUR_IP:1234 -t web web/index.php
```
