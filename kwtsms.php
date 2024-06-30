<?php
/**
Plugin Name: kwt_sms_965
Plugin URI: https://github.com/amalviju/
Description: Sends SMS notifications for WooCommerce order status changes.
Version: 1.0
Author: qwerty_1999
Author URI: #
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class KWT_SMS_965 {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        if (class_exists('WooCommerce')) {
            // Hook into WooCommerce order status changes
            add_action('woocommerce_order_status_pending', array($this, 'send_sms_on_pending_order'));
            add_action('woocommerce_order_status_processing', array($this, 'send_sms_on_processing_order'));
            add_action('woocommerce_order_status_completed', array($this, 'send_sms_on_completed_order'));
            
            // Add settings page and register settings
            add_action('admin_menu', array($this, 'add_settings_page'));
            add_action('admin_init', array($this, 'register_settings'));
        } else {
            add_action('admin_notices', array($this, 'woocommerce_not_installed_notice'));
        }
    }

    // Send SMS when order status is pending
    public function send_sms_on_pending_order($order_id) {
        $order = wc_get_order($order_id);
        $message_template = get_option('kwt_sms_pending_message', 'Your order #{order_number} is pending. We will notify you once it is confirmed.');
        $message = str_replace('{order_number}', $order->get_order_number(), $message_template);
        $this->send_sms($order, $message);
    }

    // Send SMS when order status is processing
    public function send_sms_on_processing_order($order_id) {
        $order = wc_get_order($order_id);
        $message_template = get_option('kwt_sms_processing_message', 'Your order #{order_number} is being processed. Thank you for your patience.');
        $message = str_replace('{order_number}', $order->get_order_number(), $message_template);
        $this->send_sms($order, $message);
    }

    // Send SMS when order status is completed
    public function send_sms_on_completed_order($order_id) {
        $order = wc_get_order($order_id);
        $message_template = get_option('kwt_sms_completed_message', 'Your order #{order_number} has been completed. Thank you for shopping with us!');
        $message = str_replace('{order_number}', $order->get_order_number(), $message_template);
        $this->send_sms($order, $message);
    }

    // Function to send SMS using the API
    public function send_sms($order, $message) {
        $url = "https://www.kwtsms.com/API/send/";
        $username = get_option('kwt_sms_username');
        $password = get_option('kwt_sms_password');
        $sender = get_option('kwt_sms_sender');
        $mobileNumbers = ltrim($order->get_billing_phone(), '+');
        $lang = 1; // Language code: 1 for English
        $test = get_option('kwt_sms_test_mode', 0); // Default to 0 (actual sending)

        $params = array(
            'username' => $username,
            'password' => $password,
            'sender' => $sender,
            'mobile' => $mobileNumbers,
            'lang' => $lang,
            'test' => $test,
            'message' => $message
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("SMS sending error: " . curl_error($ch));
        }

        curl_close($ch);
    }

    // Add a settings page for the plugin
    public function add_settings_page() {
        add_options_page(
            'kwt_sms_965 Settings',
            'kwt_sms_965',
            'manage_options',
            'kwt_sms_965',
            array($this, 'render_settings_page')
        );
    }

    // Register settings for the plugin
    public function register_settings() {
        register_setting('kwt_sms_settings', 'kwt_sms_username');
        register_setting('kwt_sms_settings', 'kwt_sms_password');
        register_setting('kwt_sms_settings', 'kwt_sms_sender');
        register_setting('kwt_sms_settings', 'kwt_sms_test_mode');
        register_setting('kwt_sms_settings', 'kwt_sms_pending_message');
        register_setting('kwt_sms_settings', 'kwt_sms_processing_message');
        register_setting('kwt_sms_settings', 'kwt_sms_completed_message');
    }

    // Render the settings page
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>kwt_sms_965 Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('kwt_sms_settings');
                do_settings_sections('kwt_sms_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">API Username</th>
                        <td><input type="text" name="kwt_sms_username" value="<?php echo esc_attr(get_option('kwt_sms_username')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">API Password</th>
                        <td><input type="password" name="kwt_sms_password" value="<?php echo esc_attr(get_option('kwt_sms_password')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sender ID</th>
                        <td><input type="text" name="kwt_sms_sender" value="<?php echo esc_attr(get_option('kwt_sms_sender')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Test Mode</th>
                        <td><input type="checkbox" name="kwt_sms_test_mode" value="1" <?php checked(1, get_option('kwt_sms_test_mode', 0)); ?> /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Pending Order Message</th>
                        <td><textarea name="kwt_sms_pending_message" rows="3" cols="50"><?php echo esc_textarea(get_option('kwt_sms_pending_message', 'Your order #{order_number} is pending. We will notify you once it is confirmed.')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Processing Order Message</th>
                        <td><textarea name="kwt_sms_processing_message" rows="3" cols="50"><?php echo esc_textarea(get_option('kwt_sms_processing_message', 'Your order #{order_number} is being processed. Thank you for your patience.')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Completed Order Message</th>
                        <td><textarea name="kwt_sms_completed_message" rows="3" cols="50"><?php echo esc_textarea(get_option('kwt_sms_completed_message', 'Your order #{order_number} has been completed. Thank you for shopping with us!')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // Show an admin notice if WooCommerce is not installed or activated
    public function woocommerce_not_installed_notice() {
        echo '<div class="error"><p>WooCommerce is not installed or activated. kwt_sms_965 requires WooCommerce to be installed and active.</p></div>';
    }
}

// Initialize the plugin
new KWT_SMS_965();
