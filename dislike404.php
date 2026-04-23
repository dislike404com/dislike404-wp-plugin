<?php

/**
 * Plugin Name:       dislike404.com Broken Link Checker
 * Plugin URI:        https://dislike404.com/guides/wordpress-plugin/getting-started-with-the-wordpress-plugin
 * Description:       Connect your WordPress site to dislike404.com and run 404 checks directly from your admin panel.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            dislike404.com
 * Author URI:        https://dislike404.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       dislike404-broken-link-checker
 */

if (! defined('ABSPATH')) {
    exit;
}

define('DISLIKE404_VERSION',     '1.0.0');
// To override for local development, set DISLIKE404_BASE_URL in wp-config.php.
// DISLIKE404_API_BASE will be derived from it automatically.
// Do not override DISLIKE404_API_BASE independently.
if (! defined('DISLIKE404_BASE_URL')) {
    define('DISLIKE404_BASE_URL', 'https://api.dislike404.com');
}
if (! defined('DISLIKE404_API_BASE')) {
    define('DISLIKE404_API_BASE', DISLIKE404_BASE_URL . '/api/v1');
}
define('DISLIKE404_OPTION_KEY',  'dislike404_settings');
define('DISLIKE404_PLUGIN_FILE', __FILE__);
define('DISLIKE404_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define('DISLIKE404_PLUGIN_URL',  plugin_dir_url(__FILE__));

require_once DISLIKE404_PLUGIN_DIR . 'includes/class-dislike404-api.php';
require_once DISLIKE404_PLUGIN_DIR . 'includes/class-dislike404-admin.php';
require_once DISLIKE404_PLUGIN_DIR . 'includes/class-dislike404-admin-bar.php';


if (is_admin()) {
    add_filter('plugin_action_links_' . plugin_basename(DISLIKE404_PLUGIN_FILE), function (array $links): array {
        $settings_link = '<a href="' . admin_url('options-general.php?page=dislike404') . '">'
            . __('Settings', 'dislike404-broken-link-checker')
            . '</a>';

        array_unshift($links, $settings_link);

        return $links;
    });
}
/**
 * Boot the plugin.
 */
function dislike404_init(): void
{
    $admin = new Dislike404_Admin();
    $admin->init();

    $admin_bar = new Dislike404_Admin_Bar();
    $admin_bar->init();
}
add_action('plugins_loaded', 'dislike404_init');

/**
 * Respond to ?dislike404_verify=1 with the verification code.
 * Works with any permalink structure — no rewrite rules needed.
 */
add_action('init', function (): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (! isset($_GET['dislike404_verify'])) {
        return;
    }

    $settings = Dislike404_Admin::get_settings();
    $uuid     = $settings['selected_url_uuid']   ?? '';
    $domain   = $settings['selected_url_domain'] ?? '';

    if (! $uuid || ! $domain) {
        status_header(404);
        exit;
    }

    $code = hash('sha256', $domain . '|' . $uuid);

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store, no-cache');
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $code;
    exit;
});

