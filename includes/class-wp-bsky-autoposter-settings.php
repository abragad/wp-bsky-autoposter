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

        // Add log viewer page
        add_submenu_page(
            null, // Hidden from menu
            __('Bluesky AutoPoster Logs', 'wp-bsky-autoposter'),
            __('Logs', 'wp-bsky-autoposter'),
            'manage_options',
            'wp-bsky-autoposter-logs',
            array($this, 'display_log_viewer_page')
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

        // Add link tracking section
        add_settings_section(
            'wp_bsky_autoposter_link_tracking',
            __('Link Tracking', 'wp-bsky-autoposter'),
            array($this, 'link_tracking_section_callback'),
            $this->plugin_name
        );

        // Add logging section
        add_settings_section(
            'wp_bsky_autoposter_logging',
            __('Logging', 'wp-bsky-autoposter'),
            array($this, 'logging_section_callback'),
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

        add_settings_field(
            'inline_hashtags',
            __('Inline Hashtags', 'wp-bsky-autoposter'),
            array($this, 'inline_hashtags_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        // Add base_url field
        add_settings_field(
            'base_url',
            __('Base URL', 'wp-bsky-autoposter'),
            array($this, 'base_url_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_main'
        );

        // Add link tracking fields
        add_settings_field(
            'enable_link_tracking',
            __('Enable Link Tracking', 'wp-bsky-autoposter'),
            array($this, 'enable_link_tracking_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_link_tracking'
        );

        add_settings_field(
            'utm_source',
            __('UTM Source', 'wp-bsky-autoposter'),
            array($this, 'utm_source_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_link_tracking'
        );

        add_settings_field(
            'utm_medium',
            __('UTM Medium', 'wp-bsky-autoposter'),
            array($this, 'utm_medium_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_link_tracking'
        );

        add_settings_field(
            'utm_campaign',
            __('UTM Campaign', 'wp-bsky-autoposter'),
            array($this, 'utm_campaign_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_link_tracking'
        );

        add_settings_field(
            'utm_term',
            __('UTM Term', 'wp-bsky-autoposter'),
            array($this, 'utm_term_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_link_tracking'
        );

        add_settings_field(
            'utm_content',
            __('UTM Content', 'wp-bsky-autoposter'),
            array($this, 'utm_content_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_link_tracking'
        );

        // Add logging fields
        add_settings_field(
            'log_level',
            __('Log Level', 'wp-bsky-autoposter'),
            array($this, 'log_level_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_logging'
        );

        add_settings_field(
            'log_file_location',
            __('Log File Location', 'wp-bsky-autoposter'),
            array($this, 'log_file_location_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_logging'
        );

        add_settings_field(
            'custom_log_path',
            __('Custom Log Path', 'wp-bsky-autoposter'),
            array($this, 'custom_log_path_callback'),
            $this->plugin_name,
            'wp_bsky_autoposter_logging'
        );

        // Add Yoast SEO section only if Yoast SEO is active
        if ($this->is_yoast_seo_active()) {
            add_settings_section(
                'wp_bsky_autoposter_yoast',
                __('Yoast SEO Metadata', 'wp-bsky-autoposter'),
                array($this, 'yoast_section_callback'),
                $this->plugin_name
            );

            add_settings_field(
                'use_yoast_metadata',
                __('Use Yoast SEO Metadata', 'wp-bsky-autoposter'),
                array($this, 'use_yoast_metadata_callback'),
                $this->plugin_name,
                'wp_bsky_autoposter_yoast'
            );
        }

        // Add AJAX handlers for test connection and log clearing
        add_action('wp_ajax_test_bluesky_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_clear_bluesky_logs', array($this, 'ajax_clear_logs'));
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
     * Link tracking section callback.
     *
     * @since    1.0.0
     */
    public function link_tracking_section_callback() {
        echo '<p>' . __('Configure UTM parameters for link tracking. You can use {id} and {slug} placeholders in the values.', 'wp-bsky-autoposter') . '</p>';
    }

    /**
     * Logging section callback.
     *
     * @since    1.2.0
     */
    public function logging_section_callback() {
        echo '<p>' . __('Configure logging settings for the plugin.', 'wp-bsky-autoposter') . '</p>';
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
        $value = isset($options['post_template']) ? $options['post_template'] : '{title} {link}';
        ?>
        <textarea name="wp_bsky_autoposter_settings[post_template]" rows="3" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Available placeholders: {title}, {excerpt}, {link}, {hashtags}</p>
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
            <?php _e('Text to use when post excerpt is empty. Supports {title}, {link}, and {hashtags} placeholders.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Inline hashtags field callback.
     *
     * @since    1.3.0
     */
    public function inline_hashtags_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['inline_hashtags']) ? $options['inline_hashtags'] : 0;
        ?>
        <input type="checkbox" id="inline_hashtags" name="wp_bsky_autoposter_settings[inline_hashtags]" 
               value="1" <?php checked(1, $value); ?>>
        <p class="description">
            <?php _e('Move matching hashtags into the main text (experimental). Only affects single-word hashtags that appear as whole words in the text.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Base URL field callback.
     *
     * @since    1.4.3
     */
    public function base_url_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['base_url']) ? $options['base_url'] : '';
        ?>
        <input type="url" id="base_url" name="wp_bsky_autoposter_settings[base_url]"
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Host part to replace the one exposed in your feed, if for any reason it is not correct or you want to redirect to a different site.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Enable link tracking field callback.
     *
     * @since    1.0.0
     */
    public function enable_link_tracking_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['enable_link_tracking']) ? $options['enable_link_tracking'] : 0;
        ?>
        <input type="checkbox" id="enable_link_tracking" name="wp_bsky_autoposter_settings[enable_link_tracking]" 
               value="1" <?php checked(1, $value); ?>>
        <p class="description">
            <?php _e('Enable UTM parameter tracking for links posted to Bluesky.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * UTM source field callback.
     *
     * @since    1.0.0
     */
    public function utm_source_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['utm_source']) ? $options['utm_source'] : '';
        ?>
        <input type="text" id="utm_source" name="wp_bsky_autoposter_settings[utm_source]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Suggested: bsky', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * UTM medium field callback.
     *
     * @since    1.0.0
     */
    public function utm_medium_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['utm_medium']) ? $options['utm_medium'] : '';
        ?>
        <input type="text" id="utm_medium" name="wp_bsky_autoposter_settings[utm_medium]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Suggested: social', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * UTM campaign field callback.
     *
     * @since    1.0.0
     */
    public function utm_campaign_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['utm_campaign']) ? $options['utm_campaign'] : '';
        ?>
        <input type="text" id="utm_campaign" name="wp_bsky_autoposter_settings[utm_campaign]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Suggested: feed', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * UTM term field callback.
     *
     * @since    1.0.0
     */
    public function utm_term_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['utm_term']) ? $options['utm_term'] : '';
        ?>
        <input type="text" id="utm_term" name="wp_bsky_autoposter_settings[utm_term]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Optional. You can use {id} and {slug} placeholders.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * UTM content field callback.
     *
     * @since    1.0.0
     */
    public function utm_content_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['utm_content']) ? $options['utm_content'] : '';
        ?>
        <input type="text" id="utm_content" name="wp_bsky_autoposter_settings[utm_content]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Optional. You can use {id} and {slug} placeholders.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Log level field callback.
     *
     * @since    1.2.0
     */
    public function log_level_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $log_level = isset($options['log_level']) ? $options['log_level'] : 'error';
        ?>
        <select name="wp_bsky_autoposter_settings[log_level]">
            <option value="error" <?php selected($log_level, 'error'); ?>><?php _e('Error Only', 'wp-bsky-autoposter'); ?></option>
            <option value="warning" <?php selected($log_level, 'warning'); ?>><?php _e('Warning and Above', 'wp-bsky-autoposter'); ?></option>
            <option value="success" <?php selected($log_level, 'success'); ?>><?php _e('Success and Above', 'wp-bsky-autoposter'); ?></option>
            <option value="debug" <?php selected($log_level, 'debug'); ?>><?php _e('Debug (All Messages)', 'wp-bsky-autoposter'); ?></option>
        </select>
        <p class="description">
            <?php _e('Choose the minimum level of messages to be logged. Messages below this level will not be written to the log file.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Log file location field callback.
     *
     * @since    1.2.0
     */
    public function log_file_location_callback() {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/wp-bsky-autoposter.log';
        ?>
        <p>
            <?php echo esc_html($log_file); ?>
            <br>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-bsky-autoposter-logs')); ?>" target="_blank" class="button button-secondary">
                <?php _e('View Log File', 'wp-bsky-autoposter'); ?>
            </a>
        </p>
        <p class="description">
            <?php _e('The log file is stored in your WordPress uploads directory.', 'wp-bsky-autoposter'); ?>
        </p>
        <?php
    }

    /**
     * Custom log path field callback.
     *
     * @since    1.2.0
     */
    public function custom_log_path_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $custom_path = isset($options['custom_log_path']) ? $options['custom_log_path'] : '';
        ?>
        <input type="text" 
               name="wp_bsky_autoposter_settings[custom_log_path]" 
               value="<?php echo esc_attr($custom_path); ?>" 
               class="regular-text"
               placeholder="<?php echo esc_attr(wp_upload_dir()['basedir'] . '/wp-bsky-autoposter.log'); ?>"
        />
        <p class="description">
            <?php _e('Leave empty to use the default location in the WordPress uploads directory. The path must be writable by the web server.', 'wp-bsky-autoposter'); ?>
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

        // Validate inline hashtags setting
        $valid['inline_hashtags'] = isset($input['inline_hashtags']) ? 1 : 0;

        // Validate base_url
        if (!empty($input['base_url'])) {
            $base_url = esc_url_raw(trim($input['base_url']));
            if (filter_var($base_url, FILTER_VALIDATE_URL)) {
                $valid['base_url'] = untrailingslashit($base_url);
            } else {
                $valid['base_url'] = '';
                add_settings_error(
                    'wp_bsky_autoposter_settings',
                    'invalid_base_url',
                    __('The Base URL must be a valid URL (including http:// or https://).', 'wp-bsky-autoposter')
                );
            }
        } else {
            $valid['base_url'] = '';
				}

        // Validate Yoast SEO metadata setting (only if Yoast SEO is active)
        if ($this->is_yoast_seo_active()) {
            $valid['use_yoast_metadata'] = isset($input['use_yoast_metadata']) ? 1 : 0;
        }

        // Validate link tracking settings
        $valid['enable_link_tracking'] = isset($input['enable_link_tracking']) ? 1 : 0;
        $valid['utm_source'] = sanitize_text_field($input['utm_source']);
        $valid['utm_medium'] = sanitize_text_field($input['utm_medium']);
        $valid['utm_campaign'] = sanitize_text_field($input['utm_campaign']);
        $valid['utm_term'] = sanitize_text_field($input['utm_term']);
        $valid['utm_content'] = sanitize_text_field($input['utm_content']);

        // Validate log level
        $valid['log_level'] = sanitize_text_field($input['log_level']);

        // Validate custom log path
        if (!empty($input['custom_log_path'])) {
            $path = sanitize_text_field($input['custom_log_path']);
            // Check if the directory is writable
            $dir = dirname($path);
            if (is_dir($dir) && is_writable($dir)) {
                $valid['custom_log_path'] = $path;
            } else {
                add_settings_error(
                    'wp_bsky_autoposter_settings',
                    'invalid_log_path',
                    __('The specified log directory is not writable. Please choose a different location.', 'wp-bsky-autoposter')
                );
            }
        } else {
            $valid['custom_log_path'] = '';
        }

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
     * Display the log viewer page.
     *
     * @since    1.2.0
     */
    public function display_log_viewer_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get log file path
        $settings = get_option('wp_bsky_autoposter_settings');
        $log_file = !empty($settings['custom_log_path']) 
            ? $settings['custom_log_path'] 
            : wp_upload_dir()['basedir'] . '/wp-bsky-autoposter.log';

        // Read log file
        $log_contents = '';
        if (file_exists($log_file)) {
            $log_contents = file_get_contents($log_file);
            if ($log_contents === false) {
                $log_contents = __('Error reading log file.', 'wp-bsky-autoposter');
            }
        } else {
            $log_contents = __('Log file does not exist.', 'wp-bsky-autoposter');
        }

        // Format log entries
        $log_entries = array_filter(explode("\n", $log_contents));
        ?>
        <div class="wrap">
            <h1><?php _e('Bluesky AutoPoster Logs', 'wp-bsky-autoposter'); ?></h1>
            
            <div class="log-viewer-controls">
                <button type="button" class="button button-secondary" id="refresh-logs">
                    <?php _e('Refresh', 'wp-bsky-autoposter'); ?>
                </button>
                <button type="button" class="button button-secondary" id="clear-logs">
                    <?php _e('Clear Logs', 'wp-bsky-autoposter'); ?>
                </button>
            </div>

            <div class="log-viewer-container">
                <pre class="log-content"><?php
                    foreach ($log_entries as $entry) {
                        // Color code different log levels
                        $entry = esc_html($entry);
                        if (strpos($entry, '[ERROR]') !== false) {
                            echo '<span class="log-error">' . $entry . '</span>';
                        } elseif (strpos($entry, '[WARNING]') !== false) {
                            echo '<span class="log-warning">' . $entry . '</span>';
                        } elseif (strpos($entry, '[SUCCESS]') !== false) {
                            echo '<span class="log-success">' . $entry . '</span>';
                        } elseif (strpos($entry, '[DEBUG]') !== false) {
                            echo '<span class="log-debug">' . $entry . '</span>';
                        } else {
                            echo $entry;
                        }
                        echo "\n";
                    }
                ?></pre>
            </div>

            <style>
                .log-viewer-container {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    padding: 20px;
                    margin-top: 20px;
                    max-height: 600px;
                    overflow-y: auto;
                }
                .log-content {
                    margin: 0;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }
                .log-error { color: #dc3232; }
                .log-warning { color: #ffb900; }
                .log-success { color: #46b450; }
                .log-debug { color: #00a0d2; }
                .log-viewer-controls {
                    margin: 20px 0;
                }
                .log-viewer-controls .button {
                    margin-right: 10px;
                }
            </style>

            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Refresh logs
                $('#refresh-logs').on('click', function() {
                    location.reload();
                });

                // Clear logs
                $('#clear-logs').on('click', function() {
                    if (confirm('<?php _e('Are you sure you want to clear all logs?', 'wp-bsky-autoposter'); ?>')) {
                        $.post(ajaxurl, {
                            action: 'clear_bluesky_logs',
                            nonce: '<?php echo wp_create_nonce('clear_bluesky_logs'); ?>'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data.message);
                            }
                        });
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX handler for clearing logs.
     *
     * @since    1.2.0
     */
    public function ajax_clear_logs() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clear_bluesky_logs')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-bsky-autoposter')));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wp-bsky-autoposter')));
        }

        // Get log file path
        $settings = get_option('wp_bsky_autoposter_settings');
        $log_file = !empty($settings['custom_log_path']) 
            ? $settings['custom_log_path'] 
            : wp_upload_dir()['basedir'] . '/wp-bsky-autoposter.log';

        // Clear log file
        if (file_exists($log_file)) {
            if (file_put_contents($log_file, '') === false) {
                wp_send_json_error(array('message' => __('Failed to clear log file.', 'wp-bsky-autoposter')));
            }
        }

        wp_send_json_success(array('message' => __('Logs cleared successfully.', 'wp-bsky-autoposter')));
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

    /**
     * Check if Yoast SEO is active.
     *
     * @since    1.5.0
     * @return   bool    True if Yoast SEO is active, false otherwise.
     */
    private function is_yoast_seo_active() {
        return function_exists('YoastSEO') || class_exists('WPSEO_Admin');
    }

    /**
     * Yoast SEO section callback.
     *
     * @since    1.5.0
     */
    public function yoast_section_callback() {
        echo '<p>' . __('If activated, we will check for post excerpt and other information in Yoast SEO metadata.', 'wp-bsky-autoposter') . '</p>';
        $yoast_settings_url = admin_url('admin.php?page=wpseo_page_settings#/post-type/posts');
        /* translators: %s: Link to Yoast SEO settings */
        echo '<p><a href="' . esc_url($yoast_settings_url) . '" target="_blank" class="button button-secondary">' . __('Configure Yoast SEO Settings', 'wp-bsky-autoposter') . '</a></p>';
    }

    /**
     * Use Yoast SEO metadata field callback.
     *
     * @since    1.5.0
     */
    public function use_yoast_metadata_callback() {
        $options = get_option('wp_bsky_autoposter_settings');
        $value = isset($options['use_yoast_metadata']) ? $options['use_yoast_metadata'] : 0;
        ?>
        <input type="checkbox" id="use_yoast_metadata" name="wp_bsky_autoposter_settings[use_yoast_metadata]" 
               value="1" <?php checked(1, $value); ?>>
        <?php
    }
} 