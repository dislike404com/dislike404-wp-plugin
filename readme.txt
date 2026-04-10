=== dislike404.com Broken Link Checker ===
Contributors: dislike404com
Tags: 404 checker, broken link checker, broken links, website monitoring, website scanner
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Connect your WordPress site to dislike404.com and start website scans directly from your WordPress admin panel.

== Description ==

dislike404.com monitors your website for broken links, HTTP errors, and connection failures — and alerts you by email when something goes wrong.

This plugin lets you start a scan directly from WordPress, either from the Settings page or from the admin bar at the top of every page. No need to log in to dislike404.com every time.

= Features =

* Start a website scan with a single click from the WordPress admin panel
* Optional "Scan Now" button in the WordPress admin bar for quick access on every page
* Real-time scan status — see when the scan is running, finished, or if errors were found
* Direct link to the full scan report on dislike404.com
* Connects securely to dislike404.com via an API token

= Requirements =

* A free account at [dislike404.com](https://dislike404.com)
* At least one website added to your dislike404.com account
* An API token generated in your dislike404.com profile

= Privacy and External Services =

This plugin connects to api.dislike404.com (https://api.dislike404.com) to trigger scans and retrieve scan results. The following data is sent to dislike404.com:

* Your API token (for authentication)
* The UUID of the website you want to scan

No personal data of your WordPress users is transmitted. 
Data is only sent when you actively trigger a scan, when the plugin polls for scan status updates, or when you visit the plugin settings page  (to load the list of your registered websites from dislike404.com).

By using this plugin you agree to the [dislike404.com Terms of Service](https://dislike404.com/terms-of-service) and [Privacy Policy](https://dislike404.com/privacy-policy).

== Installation ==

1. Upload the `dislike404` folder to the `/wp-content/plugins/` directory, or install via the WordPress plugin installer.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → dislike404.com** and enter your API token from dislike404.com.
4. Select the website you want to link to this WordPress installation.
5. Optionally enable the "Scan Now" button in the admin bar.

For detailed setup instructions, see the [WordPress plugin guide](https://dislike404.com/guides/wordpress-plugin/getting-started-with-the-wordpress-plugin).

== Frequently Asked Questions ==

= Where do I find my API token? =

Log in to [dislike404.com](https://dislike404.com), go to your profile, and scroll down to the WordPress Plugin section. Click "Generate Token" — the token will only be shown once, so copy it immediately.

= How many manual scans can I trigger per day? =

The daily limit depends on whether your website is verified on dislike404.com. The plugin will show a message if the limit is reached.

= Does the plugin slow down my website? =

No. The plugin only communicates with the dislike404.com API when you actively click the scan button. It has no impact on frontend performance and loads no scripts for regular website visitors.

= Does the plugin collect any data from my visitors? =

No. The plugin only runs in the WordPress admin panel and is only accessible to administrators. No visitor data is collected or transmitted.

= What happens when I uninstall the plugin? =

All plugin settings stored in WordPress (API token, selected website, preferences) are deleted on uninstall. Your data on dislike404.com is not affected.

== Screenshots ==

1. Plugin settings page — connect your dislike404.com account and select a website
2. Scan result shown directly in the WordPress admin panel
3. "Scan Now" button in the WordPress admin bar

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.