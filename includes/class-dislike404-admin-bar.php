<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Adds a "Trigger Scan" button to the WordPress admin bar when enabled in settings.
 */
class Dislike404_Admin_Bar
{

    public function init(): void
    {
        add_action('admin_bar_menu',        [$this, 'add_admin_bar_node'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts',    [$this, 'enqueue_assets']);
    }

    /**
     * Register the node in the admin bar.
     *
     * @param \WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_node(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (! $this->should_show()) {
            return;
        }

        $settings     = Dislike404_Admin::get_settings();
        $selected_uuid = $settings['selected_url_uuid'] ?? '';

        if (! $selected_uuid) {
            return;
        }


        $wp_admin_bar->add_node([
            'id'    => 'dislike404-scan',
            'title' => '<span class="ab-icon dashicons dashicons-search"></span>'
                . '<span class="dislike404-bar-label"></span>',
            'href'  => '#',
            'meta'  => [
                'class' => 'dislike404-admin-bar-btn',
            ],
        ]);
    }

    /**
     * Enqueue the admin bar JavaScript (front-end + back-end).
     */
    public function enqueue_assets(): void
    {
        if (! $this->should_show()) {
            return;
        }

        $settings     = Dislike404_Admin::get_settings();
        $selected_uuid = $settings['selected_url_uuid'] ?? '';

        if (! $selected_uuid) {
            return;
        }

        wp_enqueue_script(
            'dislike404-admin-bar',
            DISLIKE404_PLUGIN_URL . 'assets/admin-bar.js',
            ['jquery'],
            DISLIKE404_VERSION,
            true
        );

        wp_localize_script('dislike404-admin-bar', 'dislike404Bar', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('dislike404_trigger_scan'),
            'poll_nonce'  => wp_create_nonce('dislike404_scan_status'),
            'uuid'        => $selected_uuid,
            'settings_url' => admin_url('options-general.php?page=dislike404'),
            'i18n'        => [
                // buttons
                'default' => __('Scan Now', 'dislike404-broken-link-checker'),
                'initiating'    => __('Starting scan...', 'dislike404-broken-link-checker'),
                'running'       => __('Scan: ', 'dislike404-broken-link-checker'),
                'no_errors'     => __('No errors', 'dislike404-broken-link-checker'),
                'errors_found'  => __('Errors found', 'dislike404-broken-link-checker'),
                'error'         => __('Something went wrong. Please try again.', 'dislike404-broken-link-checker'),
                'error_auth'    => __('Invalid API token', 'dislike404-broken-link-checker'),
                // tooltips
                'tooltip_default'   => __('Scan your website now with dislike404.com', 'dislike404-broken-link-checker'),
                'tooltip_initiating'   => __('Scan queued - dislike404.com', 'dislike404-broken-link-checker'),
                'tooltip_running'   => __('Scan in progress. Errors found: ', 'dislike404-broken-link-checker'),
                'tooltip_running_crawled_pages'   => __(' Crawled Pages: ', 'dislike404-broken-link-checker'),
                'tooltip_no_errors' => __('No errors found in the last scan - dislike404.com', 'dislike404-broken-link-checker'),
                'tooltip_errors_found'    => __('Click to view the full report - dislike404.com', 'dislike404-broken-link-checker'),
                'tooltip_error' => __('dislike404.com: Something went wrong. Please try again  - dislike404.com', 'dislike404-broken-link-checker'),
                'tooltip_error_auth'    => __('Invalid API token. Please check your plugin settings  - dislike404.com', 'dislike404-broken-link-checker'),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function should_show(): bool
    {
        if (! is_admin_bar_showing()) {
            return false;
        }

        if (! current_user_can('manage_options')) {
            return false;
        }

        $settings = Dislike404_Admin::get_settings();

        return ! empty($settings['show_in_admin_bar'])
            && ! empty($settings['api_token']);
    }
}
