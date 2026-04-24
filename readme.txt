=== dislike404.com Broken Link Checker ===
Contributors: michaelrenz
Tags: broken links, broken link checker, 404, SEO, website monitoring
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Free automatic broken link checker for WordPress — detect 404 errors and HTTP issues, get email alerts and trigger scans from your admin panel.

== Description ==

[dislike404.com](https://dislike404.com) is a free, cloud-based broken link checker that automatically monitors your website for 404 errors, broken links, and HTTP issues — and sends you email alerts before search engines or visitors notice anything is wrong.

The scanning runs entirely on dislike404.com's servers, not on your WordPress installation. This plugin gives you a convenient way to trigger on-demand scans and check results directly from your WordPress admin panel — without having to log in to dislike404.com each time.

dislike404.com is completely free. Unverified websites scan up to 200 pages; once you verify your site, the limit rises to 50,000 pages per scan.

= What dislike404.com does =

* Automatically scans your website on a configurable schedule (daily to monthly)
* Checks internal pages, external links, images, and scripts for HTTP errors
* Validates redirect chains across multiple hops
* Sends email alerts when broken links or errors are detected
* GDPR-compliant — servers hosted in Germany, no third-party trackers

= What this plugin adds =

* Trigger an on-demand scan with a single click from the WordPress Settings page
* Optional "Scan Now" button in the WordPress admin bar for quick access on every page
* Real-time scan status — see when a scan is running, finished, or if errors were found
* Direct link to the full scan report on dislike404.com
* Verify your website directly from the plugin settings (if WordPress is installed at the root) — manual verification via dislike404.com is always available as an alternative

= Requirements =

* A free account at [dislike404.com](https://dislike404.com)
* Your WordPress site added to your dislike404.com account
* An API token generated in your dislike404.com profile

= Privacy and External Services =

This plugin connects to api.dislike404.com (https://api.dislike404.com) to trigger scans and retrieve scan results. The following data is sent to dislike404.com:

* Your API token (for authentication)
* The UUID of the website you want to scan

No personal data of your WordPress users is transmitted.
Data is only sent when you actively trigger a scan, when the plugin polls for scan status updates, or when you visit the plugin settings page (to load the list of your registered websites from dislike404.com).

By using this plugin you agree to the [dislike404.com Terms of Service](https://dislike404.com/terms-of-service) and [Privacy Policy](https://dislike404.com/privacy-policy).

== Installation ==

1. Install the plugin via the WordPress plugin installer, or download it from [WordPress.org](https://wordpress.org/plugins/dislike404-broken-link-checker/).
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → dislike404.com** and enter your API token from dislike404.com.
4. Select the website you want to link to this WordPress installation.
5. Optionally enable the "Scan Now" button in the admin bar.

For detailed setup instructions, see the [WordPress plugin guide](https://dislike404.com/guides/wordpress-plugin/getting-started-with-the-wordpress-plugin).

== Frequently Asked Questions ==

= Do I need a dislike404.com account to use this plugin? =

Yes — but creating an account is completely free. The plugin connects to your dislike404.com account via an API token to trigger scans and retrieve results.

= Is dislike404.com really free? =

Yes, completely free. Unverified websites can scan up to 200 pages per scan. Once you verify your website, the limit rises to 50,000 pages per scan. No paid plans, no hidden costs.

= What is the difference between a verified and an unverified website? =

Verification confirms that you own the website. Unverified websites are limited to 200 pages and 25 on-demand scans per day. Verified websites can scan up to 50,000 pages and trigger up to 100 on-demand scans per day. Verification can be done directly from the plugin settings page (if WordPress is installed at the root) or manually via dislike404.com.

= Where do I find my API token? =

Log in to [dislike404.com](https://dislike404.com), go to your profile, and scroll down to the WordPress Plugin section. Click "Generate Token" — the token will only be shown once, so copy it immediately.

= How many on-demand scans can I trigger per day? =

Unverified websites can trigger up to 25 on-demand scans per day; verified websites up to 100. The plugin will show a message if the limit is reached. Note that dislike404.com also scans your site automatically on a configurable schedule, independently of this limit.

= Does the plugin slow down my website? =

No. The plugin only communicates with the dislike404.com API when you actively click the scan button. It has no impact on frontend performance and loads no scripts for regular website visitors.

= Does the plugin collect any data from my visitors? =

No. The plugin only runs in the WordPress admin panel and is only accessible to administrators. No visitor data is collected or transmitted.

= What happens when I uninstall the plugin? =

All plugin settings stored in WordPress (API token, selected website, preferences) are deleted on uninstall. Your data on dislike404.com is not affected.

= Does dislike404.com work with non-WordPress websites? =

Yes. dislike404.com is a platform-independent service and works with any website — WordPress, Shopify, Webflow, or custom-built. This plugin is simply a convenient integration for WordPress users. For all other platforms, you can use dislike404.com directly.

== Screenshots ==

1. Plugin settings page — connect your dislike404.com account and select a website
2. Scan result shown directly in the WordPress admin panel
3. "Scan Now" button in the WordPress admin bar

== Changelog ==

= 1.0.2 =
* Added in-plugin website verification (for WordPress installations at the root)
* Improved plugin description and FAQ for better clarity and discoverability

= 1.0.1 =
* Minor fixes for WordPress.org plugin directory compatibility

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
Adds in-plugin website verification and improves plugin description.

= 1.0.1 =
Minor compatibility fixes.

= 1.0.0 =
Initial release.