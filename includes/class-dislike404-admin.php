<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers the plugin settings page and handles saving/displaying the configuration.
 */
class Dislike404_Admin
{

    public function init(): void
    {
        add_action('admin_menu',         [$this, 'register_menu']);
        add_action('admin_init',         [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_dislike404_trigger_scan',  [$this, 'ajax_trigger_scan']);
        add_action('wp_ajax_dislike404_scan_status',    [$this, 'ajax_scan_status']);
        add_action('wp_ajax_dislike404_verify_url',    [$this, 'ajax_verify_url']);
    }

    // -------------------------------------------------------------------------
    // Menu & Page
    // -------------------------------------------------------------------------

    public function register_menu(): void
    {
        add_options_page(
            __('dislike404.com Settings', 'dislike404-broken-link-checker'),
            __('dislike404.com', 'dislike404-broken-link-checker'),
            'manage_options',
            'dislike404',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings        = $this->get_settings();
        $api_token       = $settings['api_token'] ?? '';
        $selected_uuid   = $settings['selected_url_uuid'] ?? '';
        $selected_domain = $settings['selected_url_domain'] ?? '';
        $show_in_bar     = ! empty($settings['show_in_admin_bar']);

        // Try to load URLs if a token is configured.
        $urls        = [];
        $api_error   = '';

        if ($api_token) {
            $api    = new Dislike404_API($api_token);
            $result = $api->get_urls();

            if ($result['success']) {
                $urls = $result['data'];
            } else {
                $api_error = __('Could not connect to dislike404.com. Please check your API token.', 'dislike404-broken-link-checker');
            }
        }
?>
        <div class="wrap">
            <h1>
                <img src="<?php echo esc_url(DISLIKE404_PLUGIN_URL . 'assets/logo.svg'); ?>"
                    alt="dislike404.com logo"
                    style="height:48px;vertical-align:middle;margin-right:8px;">
                <?php esc_html_e('dislike404.com Settings', 'dislike404-broken-link-checker'); ?>
            </h1>

            <p>
                <?php
                echo wp_kses_post(sprintf(
                    /* translators: %s: link to dislike404.com */
                    __('%s monitors your website for broken links, HTTP errors, and connection failures — and alerts you by email when something goes wrong.', 'dislike404-broken-link-checker'),
                    '<a href="https://dislike404.com" target="_blank" rel="noopener noreferrer">dislike404.com</a>'
                ));
                ?>
            </p>

            <p>
                <?php esc_html_e('This plugin lets you start a scan directly from WordPress, either from this page or from the admin bar at the top. No need to log in to dislike404.com every time.', 'dislike404-broken-link-checker'); ?>
            </p>

            <p>
                <?php
                echo wp_kses_post(sprintf(
                    /* translators: %s: link to the WordPress plugin guide */
                    __('New here? The %s walks you through the setup.', 'dislike404-broken-link-checker'),
                    '<a href="https://dislike404.com/guides/wordpress-plugin/getting-started-with-the-wordpress-plugin" target="_blank" rel="noopener noreferrer">'
                        . esc_html__('WordPress plugin guide', 'dislike404-broken-link-checker')
                        . '</a>'
                ));
                ?>
            </p>

            <hr>

            <form method="post" action="options.php">
                <?php settings_fields('dislike404_settings_group'); ?>

                <table class="form-table" role="presentation">

                    <!-- API Token -->
                    <tr>
                        <th scope="row">
                            <label for="dislike404_api_token">
                                <?php esc_html_e('API Token', 'dislike404-broken-link-checker'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="dislike404_api_token"
                                name="<?php echo esc_attr(DISLIKE404_OPTION_KEY); ?>[api_token]"
                                value="<?php echo esc_attr($api_token); ?>"
                                class="regular-text"
                                autocomplete="new-password">
                            <p class="description">
                                <?php
                                echo wp_kses_post(sprintf(
                                    /* translators: %s: link to dislike404.com profile */
                                    __('Generate your API token in your %s profile settings.', 'dislike404-broken-link-checker'),
                                    '<a href="https://dislike404.com/user/profile" target="_blank" rel="noopener noreferrer">dislike404.com</a>'
                                ));
                                ?>
                            </p>
                            <?php if ($api_error) : ?>
                                <p class="notice notice-error" style="padding:8px 12px;margin-top:6px;">
                                    <?php echo esc_html($api_error); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- URL Selection -->
                    <?php if ($api_token && ! $api_error) : ?>
                        <tr>
                            <th scope="row">
                                <label for="dislike404_selected_url_uuid">
                                    <?php esc_html_e('Website to Scan', 'dislike404-broken-link-checker'); ?>
                                </label>
                            </th>
                            <td>
                                <?php if (empty($urls)) : ?>
                                    <p class="description">
                                        <?php esc_html_e('No websites found in your dislike404 account. Please add one first.', 'dislike404-broken-link-checker'); ?>
                                    </p>
                                <?php else : ?>
                                    <input
                                        type="hidden"
                                        id="dislike404_selected_url_domain"
                                        name="<?php echo esc_attr(DISLIKE404_OPTION_KEY); ?>[selected_url_domain]"
                                        value="<?php echo esc_attr($selected_domain); ?>">
                                    <select
                                        id="dislike404_selected_url_uuid"
                                        name="<?php echo esc_attr(DISLIKE404_OPTION_KEY); ?>[selected_url_uuid]"
                                        class="regular-text">
                                        <option value="" data-domain="">— <?php esc_html_e('Select a website', 'dislike404-broken-link-checker'); ?> —</option>
                                        <?php foreach ($urls as $url_entry) :
                                            // Skip entries with missing or malformed data.
                                            if (empty($url_entry['uuid']) || ! preg_match('/^[0-9a-f\-]{36}$/i', $url_entry['uuid'])) {
                                                continue;
                                            }
                                            if (empty($url_entry['url']) || ! filter_var($url_entry['url'], FILTER_VALIDATE_URL)) {
                                                continue;
                                            }
                                        ?>
                                            <option
                                                value="<?php echo esc_attr($url_entry['uuid']); ?>"
                                                data-domain="<?php echo esc_attr($url_entry['domain'] ?? ''); ?>"
                                                <?php selected($selected_uuid, $url_entry['uuid']); ?>>
                                                <?php echo esc_html($url_entry['url']); ?>
                                                <?php if (empty($url_entry['verified'])) : ?>
                                                    (<?php esc_html_e('unverified', 'dislike404-broken-link-checker'); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Admin Bar Toggle -->
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Admin Bar Button', 'dislike404-broken-link-checker'); ?>
                            </th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(DISLIKE404_OPTION_KEY); ?>[show_in_admin_bar]"
                                        value="1"
                                        <?php checked($show_in_bar); ?>>
                                    <?php esc_html_e('Show "Scan Now" button in the WordPress admin bar', 'dislike404-broken-link-checker'); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endif; ?>

                </table>

                <?php submit_button(__('Save Settings', 'dislike404-broken-link-checker')); ?>
            </form>

            <!-- Verify Website -->
            <?php
            $selected_url_data = null;
            foreach ($urls as $u) {
                if (($u['uuid'] ?? '') === $selected_uuid) {
                    $selected_url_data = $u;
                    break;
                }
            }
            $site_host     = (string) wp_parse_url(home_url(), PHP_URL_HOST);
            $site_domain   = strtolower((string) preg_replace('/^www\./i', '', $site_host));
            $url_domain    = strtolower((string) ($selected_url_data['domain'] ?? ''));
            $domain_matches = $site_domain !== '' && $url_domain !== '' && $site_domain === $url_domain;
            $is_unverified = $selected_uuid && $selected_url_data && empty($selected_url_data['verified']) && $domain_matches;
            ?>
            <?php if ($is_unverified) : ?>
                <hr>
                <h2><?php esc_html_e('Verify Your Website', 'dislike404-broken-link-checker'); ?></h2>
                <p>
                    <?php esc_html_e('Your website is not yet verified. Because the plugin is installed here, verification is automatic — just click the button below.', 'dislike404-broken-link-checker'); ?>
                </p>
                <button
                    id="dislike404-verify-btn"
                    class="button button-secondary"
                    data-uuid="<?php echo esc_attr($selected_uuid); ?>">
                    <?php esc_html_e('Verify Now', 'dislike404-broken-link-checker'); ?>
                </button>
                <p id="dislike404-verify-status" style="margin-top:8px;display:none;font-size:16px;font-weight:600;"></p>
            <?php endif; ?>

            <!-- Manual Scan Now on Settings Page -->
            <?php if ($api_token && $selected_uuid && ! $api_error) : ?>
                <hr>
                <h2><?php esc_html_e('Scan Now', 'dislike404-broken-link-checker'); ?></h2>
                <p><?php esc_html_e('Start a scan for the selected website.', 'dislike404-broken-link-checker'); ?></p>
                <button
                    id="dislike404-trigger-scan-btn"
                    class="button button-primary "
                    data-uuid="<?php echo esc_attr($selected_uuid); ?>">
                    <?php esc_html_e('▶ Run Scan Now', 'dislike404-broken-link-checker'); ?>
                </button>

                <div id="dislike404-last-scan" style="margin-top:16px;display:none;">
                    <strong><?php esc_html_e('Last scan result:', 'dislike404-broken-link-checker'); ?></strong><br>
                    <span id="dislike404-scan-status" style="margin-top:4px;display:inline-block;"></span>
                </div>
            <?php endif; ?>

        </div>
<?php
    }

    // -------------------------------------------------------------------------
    // Settings Registration
    // -------------------------------------------------------------------------

    public function register_settings(): void
    {
        register_setting(
            'dislike404_settings_group',
            DISLIKE404_OPTION_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => [],
            ]
        );
    }

    /**
     * Sanitize and validate incoming settings before storing them.
     *
     * @param  mixed $input
     * @return array
     */
    public function sanitize_settings($input): array
    {
        if (! is_array($input)) {
            return [];
        }
        $clean = [];

        $clean['api_token'] = substr(sanitize_text_field($input['api_token'] ?? ''), 0, 255);

        // Only allow UUIDs (basic pattern check).
        $uuid = sanitize_text_field($input['selected_url_uuid'] ?? '');
        $clean['selected_url_uuid'] = preg_match('/^[0-9a-f\-]{36}$/i', $uuid) ? $uuid : '';

        // Domain: bare hostname without www, alphanumeric + dots + hyphens.
        $domain = sanitize_text_field($input['selected_url_domain'] ?? '');
        $clean['selected_url_domain'] = preg_match('/^[a-z0-9][a-z0-9\-\.]{0,251}[a-z0-9]$/i', $domain) ? $domain : '';

        $clean['show_in_admin_bar'] = ! empty($input['show_in_admin_bar']) ? 1 : 0;

        return $clean;
    }

    // -------------------------------------------------------------------------
    // AJAX — Trigger Scan
    // -------------------------------------------------------------------------

    public function ajax_trigger_scan(): void
    {
        check_ajax_referer('dislike404_trigger_scan', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'dislike404-broken-link-checker')], 403);
        }

        $settings = $this->get_settings();
        $api_token = $settings['api_token'] ?? '';
        $uuid      = sanitize_text_field(wp_unslash($_POST['uuid'] ?? ''));

        if (! $api_token) {
            wp_send_json_error(['message' => __('No API token configured.', 'dislike404-broken-link-checker')]);
        }

        if (! preg_match('/^[0-9a-f\-]{36}$/i', $uuid)) {
            wp_send_json_error(['message' => __('Invalid URL UUID.', 'dislike404-broken-link-checker')]);
        }

        $api    = new Dislike404_API($api_token);
        $result = $api->trigger_scan($uuid);

        if ($result['success']) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['status_code' => $result['status_code'] ?? 0]);
        }
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public function enqueue_assets(string $hook): void
    {
        // Only load on the plugin settings page.
        if ('settings_page_dislike404' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'dislike404-admin',
            DISLIKE404_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            DISLIKE404_VERSION,
            true
        );

        wp_localize_script('dislike404-admin', 'dislike404Ajax', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('dislike404_trigger_scan'),
            'poll_nonce'  => wp_create_nonce('dislike404_scan_status'),
            'verify_nonce' => wp_create_nonce('dislike404_verify_url'),
            'i18n'        => [
                'idle'           => __('No previous scan found.', 'dislike404-broken-link-checker'),
                'initiating'     => __('Starting scan...', 'dislike404-broken-link-checker'),
                'running'        => __('Scan in Progress:', 'dislike404-broken-link-checker'),
                'no_errors'      => __('No errors found', 'dislike404-broken-link-checker'),
                'errors_found'   => __('error(s) found', 'dislike404-broken-link-checker'),
                'pages_crawled'  => __('pages crawled', 'dislike404-broken-link-checker'),
                'view_report'    => __('View report', 'dislike404-broken-link-checker'),
                'error'          => __('Something went wrong. Please try again.', 'dislike404-broken-link-checker'),
                'error_auth'     => __('Invalid API token. Please check your plugin settings.', 'dislike404-broken-link-checker'),
                'verifying'      => __('Verifying…', 'dislike404-broken-link-checker'),
                'verify_success' => __('Verified! Reloading…', 'dislike404-broken-link-checker'),
                'verify_error'   => __('Verification failed. Make sure the plugin is active and your settings are saved, then try again.', 'dislike404-broken-link-checker'),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // AJAX — Verify URL
    // -------------------------------------------------------------------------

    public function ajax_verify_url(): void
    {
        check_ajax_referer('dislike404_verify_url', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'dislike404-broken-link-checker')], 403);
        }

        $settings  = $this->get_settings();
        $api_token = $settings['api_token'] ?? '';
        $uuid      = sanitize_text_field(wp_unslash($_POST['uuid'] ?? ''));

        if (! $api_token) {
            wp_send_json_error(['message' => __('No API token configured.', 'dislike404-broken-link-checker')]);
        }

        if (! preg_match('/^[0-9a-f\-]{36}$/i', $uuid)) {
            wp_send_json_error(['message' => __('Invalid URL UUID.', 'dislike404-broken-link-checker')]);
        }

        $api    = new Dislike404_API($api_token);
        $result = $api->verify_url($uuid);

        if ($result['success']) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => $result['message'] ?? __('Verification failed.', 'dislike404-broken-link-checker')]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public static function get_settings(): array
    {
        return (array) get_option(DISLIKE404_OPTION_KEY, []);
    }

    // -------------------------------------------------------------------------
    // AJAX — Poll Scan Status
    // -------------------------------------------------------------------------

    public function ajax_scan_status(): void
    {
        check_ajax_referer('dislike404_scan_status', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'dislike404-broken-link-checker')], 403);
        }

        $settings  = $this->get_settings();
        $api_token = $settings['api_token'] ?? '';
        $uuid      = sanitize_text_field(wp_unslash($_POST['uuid'] ?? ''));

        if (! $api_token) {
            wp_send_json_error(['message' => __('No API token configured.', 'dislike404-broken-link-checker')]);
        }

        if (! preg_match('/^[0-9a-f\-]{36}$/i', $uuid)) {
            wp_send_json_error(['message' => __('Invalid URL UUID.', 'dislike404-broken-link-checker')]);
        }

        $api    = new Dislike404_API($api_token);
        $result = $api->get_scan_status($uuid);

        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error(['status_code' => $result['status_code'] ?? 0]);
        }
    }
}
