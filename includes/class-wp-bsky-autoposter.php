<?php
/**
 * The main plugin class.
 *
 * @since      1.0.0
 * @package    WP_BSky_AutoPoster
 */

class WP_BSky_AutoPoster {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WP_BSky_AutoPoster_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'wp-bsky-autoposter';
        $this->version = WP_BSKY_AUTOPOSTER_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Load settings class
        $this->settings = new WP_BSky_AutoPoster_Settings();
        
        // Load API class
        $this->api = new WP_BSky_AutoPoster_API();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Add settings page
        add_action('admin_menu', array($this->settings, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this->settings, 'register_settings'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(WP_BSKY_AUTOPOSTER_PLUGIN_DIR . 'wp-bsky-autoposter.php'), 
            array($this->settings, 'add_action_links'));

        // Hook into post publication
        add_action('publish_post', array($this, 'handle_post_publication'), 10, 2);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // No public hooks needed for now
    }

    /**
     * Handle post publication and send to Bluesky.
     *
     * @since    1.0.0
     * @param    int     $post_id    The post ID.
     * @param    WP_Post $post       The post object.
     */
    public function handle_post_publication($post_id, $post) {
        // Don't process revisions or autosaves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Get the post's status
        $post_status = get_post_status($post_id);
        
        // Skip if this is an update (not a new post or scheduled post being published)
        if ($post_status !== 'publish' || 
            ($post->post_date !== $post->post_modified && 
             strtotime($post->post_modified) - strtotime($post->post_date) > 10)) {
            return;
        }

        // Get plugin settings
        $settings = get_option('wp_bsky_autoposter_settings');
        if (empty($settings['bluesky_handle']) || empty($settings['app_password'])) {
            return;
        }

        // Format the post content
        $message = $this->format_post_message($post, $settings['post_template']);

        // Get post metadata for rich preview
        $preview_data = $this->get_post_preview_data($post);

        // Send to Bluesky
        $this->api->post_to_bluesky($message, $preview_data, $post_id);
    }

    /**
     * Truncate text to fit within AT Protocol's 300 graphemes limit.
     *
     * @since    1.1.0
     * @param    string    $text    The text to truncate.
     * @return   string    The truncated text.
     */
    private function truncate_for_at_protocol($text) {
        // Remove HTML tags and decode entities
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Count graphemes (characters that may be composed of multiple code points)
        $grapheme_count = mb_strlen($text, 'UTF-8');
        
        if ($grapheme_count <= 300) {
            return $text;
        }
        
        // Truncate to 297 characters and add ellipsis
        return mb_substr($text, 0, 297, 'UTF-8') . '...';
    }

    /**
     * Format the post message using the template.
     *
     * @since    1.0.0
     * @param    WP_Post $post     The post object.
     * @param    string  $template The message template.
     * @return   string  The formatted message.
     */
    private function format_post_message($post, $template) {
        // Get hashtags
        $hashtags = $this->api->get_hashtags($post->ID);

        // Get the post link with UTM parameters if enabled
        $link = $this->get_post_link_with_utm($post);

        // Get plugin settings
        $settings = get_option('wp_bsky_autoposter_settings');

        // Get excerpt or fallback text
        $excerpt = get_the_excerpt($post);
        if (empty($excerpt) && !empty($settings['fallback_text'])) {
            // Process placeholders in fallback text
            $fallback_replacements = array(
                '{title}' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                '{link}' => $link,
                '{hashtags}' => $hashtags,
            );
            $excerpt = str_replace(array_keys($fallback_replacements), array_values($fallback_replacements), $settings['fallback_text']);
        }

        // Truncate excerpt if needed
        $excerpt = $this->truncate_for_at_protocol($excerpt);

        $replacements = array(
            '{title}' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            '{excerpt}' => $excerpt,
            '{link}' => $link,
            '{hashtags}' => $hashtags,
        );

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Ensure the final message doesn't exceed the limit
        return $this->truncate_for_at_protocol($message);
    }

    /**
     * Get post link with UTM parameters if enabled.
     *
     * @since    1.0.0
     * @param    WP_Post $post The post object.
     * @return   string  The post URL with UTM parameters if enabled.
     */
    private function get_post_link_with_utm($post) {
        $link = get_permalink($post);
        
        // Get plugin settings
        $settings = get_option('wp_bsky_autoposter_settings');
        
        // Check if link tracking is enabled
        if (empty($settings['enable_link_tracking'])) {
            return $link;
        }

        // Prepare UTM parameters
        $utm_params = array();
        
        // Process each UTM parameter
        $utm_fields = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
        foreach ($utm_fields as $field) {
            if (!empty($settings[$field])) {
                $value = $settings[$field];
                
                // Replace placeholders
                $value = str_replace(
                    array('{id}', '{slug}'),
                    array($post->ID, $post->post_name),
                    $value
                );
                
                $utm_params[$field] = urlencode($value);
            }
        }
        
        // Add UTM parameters to the URL if any exist
        if (!empty($utm_params)) {
            $link = add_query_arg($utm_params, $link);
        }
        
        return $link;
    }

    /**
     * Get post metadata for rich preview.
     *
     * @since    1.0.0
     * @param    WP_Post $post The post object.
     * @return   array   The preview data.
     */
    private function get_post_preview_data($post) {
        // Get plugin settings
        $settings = get_option('wp_bsky_autoposter_settings');

        // Get excerpt or fallback text
        $excerpt = get_the_excerpt($post);
        if (empty($excerpt) && !empty($settings['fallback_text'])) {
            // Get hashtags and link for fallback text processing
            $hashtags = $this->api->get_hashtags($post->ID);
            $link = $this->get_post_link_with_utm($post);

            // Process placeholders in fallback text
            $fallback_replacements = array(
                '{title}' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                '{link}' => $link,
                '{hashtags}' => $hashtags,
            );
            $excerpt = str_replace(array_keys($fallback_replacements), array_values($fallback_replacements), $settings['fallback_text']);
        }

        // Truncate excerpt for preview description
        $excerpt = $this->truncate_for_at_protocol($excerpt);

        $preview_data = array(
            'uri' => $this->get_post_link_with_utm($post),
            'title' => get_the_title($post),
            'description' => $excerpt,
        );

        // Get featured image if available
        if (has_post_thumbnail($post)) {
            $preview_data['thumb'] = get_the_post_thumbnail_url($post, 'large');
        }

        return $preview_data;
    }

    /**
     * Run the plugin.
     *
     * @since    1.0.0
     */
    public function run() {
        // Plugin is running
    }
} 