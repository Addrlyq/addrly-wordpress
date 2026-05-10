<?php
/**
 * Addrly - Email Validation & Protection
 *
 * @package           Addrly
 * @author            Addrly
 * @copyright         2026 Addrly
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Addrly - Email Validation
 * Plugin URI:        https://addrly.io
 * Description:       Protect your WordPress site from disposable emails and spam domains. Works out of the box with 60 requests/hour free.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Addrly
 * Author URI:        https://addrly.io
 * Text Domain:       addrly
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

define('ADDRLY_VERSION', '1.1.0');
define('ADDRLY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADDRLY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Addrly class.
 */
class Addrly
{
    /**
     * API key for Addrly (optional).
     */
    private $api_key;

    /**
     * Base URL for the Addrly API.
     */
    private $api_base_url = 'https://api.addrly.io/api/v1';

    /**
     * Maximum number of error logs to keep.
     */
    private $max_logs = 100;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->api_key = get_option('addrly_api_key', '');

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        // Validation hook - integrates with WordPress email validation
        add_filter('is_email', array($this, 'validate_email'), 10, 2);
        
        // WooCommerce integration
        add_action('woocommerce_checkout_process', array($this, 'validate_woocommerce_email'));
        
        // Contact Form 7 integration
        add_filter('wpcf7_validate_email', array($this, 'validate_cf7_email'), 20, 2);
        add_filter('wpcf7_validate_email*', array($this, 'validate_cf7_email'), 20, 2);

        // AJAX handlers
        add_action('wp_ajax_addrly_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_addrly_clear_logs', array($this, 'ajax_clear_logs'));
    }

    /**
     * Enqueue admin styles.
     */
    public function enqueue_admin_styles($hook)
    {
        if (strpos($hook, 'addrly') === false) {
            return;
        }

        wp_enqueue_style(
            'addrly-admin',
            ADDRLY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ADDRLY_VERSION
        );
    }

    /**
     * Add options page to the admin menu.
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Addrly', 'addrly'),
            __('Addrly', 'addrly'),
            'manage_options',
            'addrly',
            array($this, 'settings_page'),
            'dashicons-shield-alt',
            100
        );
    }

    /**
     * Render the settings page.
     */
    public function settings_page()
    {
        $logs = $this->get_logs();
        ?>
        <div class="wrap addrly-wrap">
            <h1>
                <span class="addrly-logo">
                    <svg width="32" height="32" viewBox="-5 -5 95 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M40,80 C17.9,80 0,62.1 0,40 C0,17.9 17.9,0 40,0 C58,0 73,12 78,28" stroke="#1d2327" stroke-width="9" stroke-linecap="round" fill="none"/>
                        <path d="M25,42 L40,57 L82,15" stroke="#3b82f6" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <circle cx="82" cy="15" r="5" fill="#3b82f6"/>
                    </svg>
                </span>
                <?php esc_html_e('Addrly Settings', 'addrly'); ?>
            </h1>

            <div class="addrly-card">
                <h2><?php esc_html_e('How It Works', 'addrly'); ?></h2>
                <p><?php esc_html_e('Addrly automatically blocks disposable and spam email addresses during:', 'addrly'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php esc_html_e('WordPress user registration', 'addrly'); ?></li>
                    <li><?php esc_html_e('Comment submissions', 'addrly'); ?></li>
                    <li><?php esc_html_e('WooCommerce checkout', 'addrly'); ?></li>
                    <li><?php esc_html_e('Contact Form 7 submissions', 'addrly'); ?></li>
                </ul>
                <p><strong><?php esc_html_e('No configuration required!', 'addrly'); ?></strong> <?php esc_html_e('The plugin works out of the box with 60 free requests per hour.', 'addrly'); ?></p>
            </div>

            <div class="addrly-card">
                <h2><?php esc_html_e('API Configuration (Optional)', 'addrly'); ?></h2>
                <p><?php esc_html_e('Add an API key to increase your request limit and access advanced features.', 'addrly'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="addrly_api_key"><?php esc_html_e('API Key', 'addrly'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="addrly_api_key" name="addrly_api_key" 
                                   value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" />
                            <button type="button" class="button button-primary" id="addrly-save-settings">
                                <?php esc_html_e('Save', 'addrly'); ?>
                            </button>
                            <span id="addrly-save-status"></span>
                            <p class="description">
                                <?php esc_html_e('Get a free API key at', 'addrly'); ?>
                                <a href="https://addrly.io/signup" target="_blank">addrly.io/signup</a>
                                <?php esc_html_e('for higher limits.', 'addrly'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="addrly-limits-info">
                    <h4><?php esc_html_e('Request Limits', 'addrly'); ?></h4>
                    <table class="widefat" style="max-width: 400px;">
                        <tr>
                            <td><?php esc_html_e('Without API Key', 'addrly'); ?></td>
                            <td><strong>60 <?php esc_html_e('requests/hour', 'addrly'); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Free Plan', 'addrly'); ?></td>
                            <td><strong>2,500 <?php esc_html_e('requests/month', 'addrly'); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Pro Plan', 'addrly'); ?></td>
                            <td><strong>10,000 <?php esc_html_e('requests/month', 'addrly'); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Ultra Plan', 'addrly'); ?></td>
                            <td><strong>250,000 <?php esc_html_e('requests/month', 'addrly'); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Scale Plan', 'addrly'); ?></td>
                            <td><strong>500,000 <?php esc_html_e('requests/month', 'addrly'); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="addrly-card">
                <div class="addrly-logs-header">
                    <h2><?php esc_html_e('Error Logs', 'addrly'); ?></h2>
                    <?php if (!empty($logs)) : ?>
                        <button type="button" class="button button-secondary" id="addrly-clear-logs">
                            <?php esc_html_e('Clear Logs', 'addrly'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:180px;"><?php esc_html_e('Timestamp', 'addrly'); ?></th>
                            <th><?php esc_html_e('Error Message', 'addrly'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)) : ?>
                            <tr>
                                <td colspan="2"><?php esc_html_e('No errors logged.', 'addrly'); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($logs as $log) : ?>
                                <tr>
                                    <td><?php echo esc_html($log['timestamp']); ?></td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="addrly-card addrly-faq">
                <h2><?php esc_html_e('FAQ', 'addrly'); ?></h2>
                
                <h4><?php esc_html_e('Do I need an API key to use this plugin?', 'addrly'); ?></h4>
                <p><?php esc_html_e('No, an API key is not required. The plugin works out of the box with a limit of 60 requests per hour. For higher limits, you can get a free API key at addrly.io.', 'addrly'); ?></p>
                
                <h4><?php esc_html_e('What happens if the API is unavailable or rate limited?', 'addrly'); ?></h4>
                <p><?php esc_html_e('If the API is unavailable or you exceed the rate limit, emails will be allowed through to prevent disruption to your site. An error will be logged for your reference.', 'addrly'); ?></p>
                
                <h4><?php esc_html_e('What types of emails are blocked?', 'addrly'); ?></h4>
                <p><?php esc_html_e('Addrly blocks disposable/temporary email addresses (like tempmail, guerrillamail) and domains known for spam activity.', 'addrly'); ?></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#addrly-save-settings').on('click', function() {
                var $btn = $(this);
                var $status = $('#addrly-save-status');
                var apiKey = $('#addrly_api_key').val();
                
                $btn.prop('disabled', true);
                $status.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span>');
                
                $.post(ajaxurl, {
                    action: 'addrly_save_settings',
                    api_key: apiKey,
                    nonce: '<?php echo wp_create_nonce('addrly_ajax'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $status.html('<span style="color:green;">✓ <?php esc_html_e('Saved!', 'addrly'); ?></span>');
                    } else {
                        $status.html('<span style="color:red;">✗ ' + response.data.message + '</span>');
                    }
                    setTimeout(function() { $status.html(''); }, 3000);
                });
            });

            $('#addrly-clear-logs').on('click', function() {
                if (!confirm('<?php esc_attr_e('Clear all error logs?', 'addrly'); ?>')) return;
                
                var $btn = $(this);
                $btn.prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'addrly_clear_logs',
                    nonce: '<?php echo wp_create_nonce('addrly_ajax'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Validate email using Addrly API.
     *
     * @param bool   $is_valid Whether the email is valid.
     * @param string $email    The email address to validate.
     * @return bool Whether the email is valid.
     */
    public function validate_email($is_valid, $email)
    {
        // Skip if already invalid
        if (!$is_valid) {
            return false;
        }

        // Skip WordPress system emails
        if ($this->is_system_email($email)) {
            return $is_valid;
        }

        // Call the API
        $result = $this->check_email($email);
        
        // If API fails, allow the email (fail open)
        if ($result === null) {
            return $is_valid;
        }

        // Block if disposable or spam
        if (!empty($result['disposable']) || !empty($result['spam'])) {
            return false;
        }

        return $is_valid;
    }

    /**
     * Check if email is a WordPress system email.
     */
    private function is_system_email($email)
    {
        $admin_email = get_option('admin_email');
        $site_host = wp_parse_url(network_home_url(), PHP_URL_HOST);
        $site_host = preg_replace('#^www\.#', '', $site_host);
        $site_email = sprintf('wordpress@%s', $site_host);

        return in_array($email, array($admin_email, $site_email), true);
    }

    /**
     * WooCommerce checkout validation.
     */
    public function validate_woocommerce_email()
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';
        
        if (empty($email)) {
            return;
        }

        $result = $this->check_email($email);
        
        // If API fails, allow (fail open)
        if ($result === null) {
            return;
        }

        if (!empty($result['disposable']) || !empty($result['spam'])) {
            wc_add_notice(__('Please use a valid email address. Disposable emails are not allowed.', 'addrly'), 'error');
        }
    }

    /**
     * Contact Form 7 validation.
     */
    public function validate_cf7_email($result, $tag)
    {
        if (!class_exists('WPCF7')) {
            return $result;
        }

        $email = isset($_POST[$tag->name]) ? sanitize_email($_POST[$tag->name]) : '';
        
        if (empty($email)) {
            return $result;
        }

        $api_result = $this->check_email($email);
        
        // If API fails, allow (fail open)
        if ($api_result === null) {
            return $result;
        }

        if (!empty($api_result['disposable']) || !empty($api_result['spam'])) {
            $result->invalidate($tag, __('Please use a valid email address. Disposable emails are not allowed.', 'addrly'));
        }

        return $result;
    }

    /**
     * Check email via Addrly API.
     *
     * @param string $email The email to check.
     * @return array|null API response or null on error.
     */
    private function check_email($email)
    {
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array('email' => $email)),
            'timeout' => 5,
        );

        // Add API key if configured
        if (!empty($this->api_key)) {
            $args['headers']['X-API-Key'] = $this->api_key;
        }

        $response = wp_remote_post($this->api_base_url . '/email', $args);

        if (is_wp_error($response)) {
            $this->log_error(__('API Connection Error: ', 'addrly') . $response->get_error_message());
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 429) {
            $this->log_error(__('API Rate Limit Exceeded (429) - Get an API key at addrly.io for higher limits.', 'addrly'));
            return null;
        }
        
        if ($code !== 200) {
            $this->log_error(sprintf(__('API Error: HTTP %d', 'addrly'), $code));
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error(__('API Response Parse Error', 'addrly'));
            return null;
        }

        return $data;
    }

    /**
     * Log an error message.
     */
    private function log_error($message)
    {
        $logs = get_option('addrly_error_logs', array());
        
        // Add new log entry
        array_unshift($logs, array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'message' => $message
        ));

        // Keep only max logs
        $logs = array_slice($logs, 0, $this->max_logs);

        update_option('addrly_error_logs', $logs);
    }

    /**
     * Get error logs.
     */
    private function get_logs()
    {
        return get_option('addrly_error_logs', array());
    }

    /**
     * AJAX handler for saving settings.
     */
    public function ajax_save_settings()
    {
        check_ajax_referer('addrly_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'addrly')));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        update_option('addrly_api_key', $api_key);
        $this->api_key = $api_key;
        
        wp_send_json_success(array('message' => __('Settings saved.', 'addrly')));
    }

    /**
     * AJAX handler for clearing logs.
     */
    public function ajax_clear_logs()
    {
        check_ajax_referer('addrly_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'addrly')));
        }

        delete_option('addrly_error_logs');
        wp_send_json_success();
    }
}

// Initialize the plugin
new Addrly();
