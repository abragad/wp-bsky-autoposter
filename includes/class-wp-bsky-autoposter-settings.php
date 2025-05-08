<?php
/**
 * The settings-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_BSky_AutoPoster
 */

class WP_BSky_AutoPoster_Settings {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'wp-bsky-autoposter';
    }

    /**
     * Add options page to the Settings menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            __('Bluesky AutoPoster Settings', 'wp-bsky-autoposter'),
            __('Bluesky AutoPoster', 'wp-bsky-autoposter'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     * @param    array    $links    Plugin action links.
     * @return   array    Plugin action links.
     */
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('options-general.php?page=' . $this->plugin_name) . '">' . 
            __('Settings', 'wp-bsky-autoposter') . '</a>',
        );
        return array_merge($settings_link, $links);
    }

    /**
     * Register all settings fields.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'wp_bsky_autoposter_settings',
            'wp_bsky_autoposter_settings',
            array($this, 'validate_settings')
        );

        add_settings_section(
            'wp_bsky_autoposter_main',
            __('Bluesky Account Settings', 'wp-bsky-autoposter'),
            array($this, 'settings_section_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'bluesky_handle',
            __('Bluesky Handle', 'wp-bsky-autoposter'),
            array($this, 'bluesky_handle_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        add_settings_field(
            'app_password',
            __('App Password', 'wp-bsky-autoposter'),
            array($this, 'app_password_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        add_settings_field(
            'test_connection',
            __('Test Connection', 'wp-bsky-autoposter'),
            array($this, 'test_connection_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        add_settings_field(
            'post_template',
            __('Post Template', 'wp-bsky-autoposter'),
            array($this, 'post_template_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        add_settings_field(
            'fallback_text',
            __('Fallback Text', 'wp-bsky-autoposter'),
            array($this, 'fallback_text_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        // Add AJAX handlers for test connection
        add_action('wp_ajax_test_bluesky_connection', array($this, 'ajax_test_connection'));
    }

    /**
     * Settings section callback.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . __('Enter your Bluesky account details and customize how posts are formatted.', 'wp-bsky-autoposter') . '</p>';
    }

    /**
     * Bluesky handle field callback.
     *
     * @since    1.0.0
     */
    public function bluesky_handle_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['bluesky_handle']) ? $options['bluesky_handle'] : '';
        ?>
        <input type="text" id="bluesky_handle" name="wp_bsky_autoposter_settings[bluesky_handle]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Enter your Bluesky handle (e.g., username.bsky.social) or DID. The @ symbol is optional.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * App password field callback.
     *
     * @since    1.0.0
     */
    public function app_password_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['app_password']) ? $options['app_password'] : '';
        ?>
        <input type="password" id="app_password" name="wp_bsky_autoposter_settings[app_password]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Enter your Bluesky App Password. You can generate this in your Bluesky account settings.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Test connection field callback.
     *
     * @since    1.0.0
     */
    public function test_connection_callback() {
        ?>
        <button type="button" id="test-bluesky-connection" class="button button-secondary">
            <?php _e('Test Connection', 'wp-bsky-autoposter'); ?>
        </button>
        <span class="spinner" style="float: none; margin-top: 4px;"></span>
        <p class="description">
            <?php _e('Test your Bluesky credentials before saving.', 'wp-bsky-autoposter'); ?>
        </p>
        <div id="test-connection-result" style="margin-top: 10px;"></div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test-bluesky-connection').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $spinner = $button.next('.spinner');
                var $result = $('#test-connection-result');
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $result.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_bluesky_connection',
                        handle: $('#bluesky_handle').val(),
                        password: $('#app_password').val(),
                        nonce: '<?php echo wp_create_nonce('test_bluesky_connection'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $result.html('<div class="notice notice-error inline"><p><?php _e('Connection test failed. Please try again.', 'wp-bsky-autoposter'); ?></p></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Post template field callback.
     *
     * @since    1.0.0
     */
    public function post_template_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['post_template']) ? $options['post_template'] : '{title} - {link}';
        ?>
        <textarea id="post_template" name="wp_bsky_autoposter_settings[post_template]" 
                  rows="3" cols="50"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('Available placeholders: {title}, {excerpt}, {link}', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Fallback text field callback.
     *
     * @since    1.0.0
     */
    public function fallback_text_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['fallback_text']) ? $options['fallback_text'] : '';
        ?>
        <input type="text" id="fallback_text" name="wp_bsky_autoposter_settings[fallback_text]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Text to use when post excerpt is empty.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Validate settings before saving.
     *
     * @since    1.0.0
     * @param    array    $input    The settings input.
     * @return   array    The validated settings.
     */
    public function validate_settings($input) {
        $valid = array();

        // Validate Bluesky handle
        $valid['bluesky_handle'] = sanitize_text_field($input['bluesky_handle']);
        // Remove @ if present
        $valid['bluesky_handle'] = ltrim($valid['bluesky_handle'], '@');
        
        if (!empty($valid['bluesky_handle']) && !preg_match('/^([a-zA-Z0-9.-]+|did:[a-zA-Z0-9:]+)$/', $valid['bluesky_handle'])) {
            add_settings_error(
                'wp_bsky_autoposter_settings',
                'invalid_handle',
                __('Invalid Bluesky handle format. Please enter a valid handle (e.g., username.bsky.social) or DID.', 'wp-bsky-autoposter')
            );
        }

        // Validate app password
        $valid['app_password'] = sanitize_text_field($input['app_password']);

        // Validate post template
        $valid['post_template'] = sanitize_textarea_field($input['post_template']);
        if (empty($valid['post_template'])) {
            $valid['post_template'] = '{title} - {link}';
        }

        // Validate fallback text
        $valid['fallback_text'] = sanitize_text_field($input['fallback_text']);

        return $valid;
    }

    /**
     * AJAX handler for testing Bluesky connection.
     *
     * @since    1.0.0
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_bluesky_connection')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-bsky-autoposter')));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-bsky-autoposter')));
        }

        // Get and validate input
        $handle = isset($_POST['handle']) ? sanitize_text_field($_POST['handle']) : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

        if (empty($handle) || empty($password)) {
            wp_send_json_error(array('message' => __('Please enter both Bluesky handle and app password.', 'wp-bsky-autoposter')));
        }

        // Remove @ if present
        $handle = ltrim($handle, '@');

        // Test connection
        $api = new WP_BSky_AutoPoster_API();
        if ($api->authenticate($handle, $password)) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Connection successful! Authenticated as %s.', 'wp-bsky-autoposter'),
                    esc_html($handle)
                )
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Connection failed. Please check your credentials and try again.', 'wp-bsky-autoposter')
            ));
        }
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_bsky_autoposter_settings');
                do_settings_sections($this->plugin_name);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
} 