<?php
/**
 * The main plugin class.
 *
 * @since      1.0.0
 * @package    WP_BSky_AutoPoster
 */

class WP_BSky_AutoPoster {

    /**
     * The settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_BSky_AutoPoster_Settings    $settings    The settings instance.
     */
    private $settings;

    /**
     * The API instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_BSky_AutoPoster_API    $api    The API instance.
     */
    private $api;

    /**
     * A temporary cache for post data to avoid redundant database calls.
     *
     * @since    1.6.0
     * @access   private
     * @var      array    $post_data_cache    A cache for post data.
     */
    private $post_data_cache = [];

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
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
        // Clear cache for each new post publication
        $this->post_data_cache[$post_id] = [];
        
        // Don't process revisions or autosaves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Get the post's status
        $post_status = get_post_status($post_id);
        
        // Get the previous status from post meta
        $previous_status = get_post_meta($post_id, '_wp_bsky_previous_status', true);
        
        // Skip if this is an update to an already published post
        if ($post_status !== 'publish' || 
            ($previous_status === 'publish' && 
             $post->post_date !== $post->post_modified && 
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
        
        // Update the previous status
        update_post_meta($post_id, '_wp_bsky_previous_status', $post_status);
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
     * Get post title with Yoast SEO priority if enabled.
     *
     * @since    1.5.0
     * @param    WP_Post $post The post object.
     * @return   string  The title text.
     */
    private function get_post_title($post) {
        if (isset($this->post_data_cache[$post->ID]['title'])) {
            return $this->post_data_cache[$post->ID]['title'];
        }

        $title = '';
        $settings = get_option('wp_bsky_autoposter_settings');
        
        // Check if Yoast SEO metadata should be used
        if (!empty($settings['use_yoast_metadata']) && $this->is_yoast_seo_active()) {
            // Priority 1: Try to get Yoast SEO Twitter title first
            $yoast_twitter_title = get_post_meta($post->ID, '_yoast_wpseo_twitter-title', true);
            if (!empty($yoast_twitter_title)) {
                /* translators: 1: Post ID, 2: Title length */
                $this->api->log_debug(sprintf(
                    __('Using Yoast SEO Twitter title for post %1$d (length: %2$d characters)', 'wp-bsky-autoposter'),
                    $post->ID,
                    strlen($yoast_twitter_title)
                ));
                $title = $yoast_twitter_title;
            }
            
            // Priority 2: Try to get Yoast SEO title
            if (empty($title)) {
                $yoast_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
                if (!empty($yoast_title)) {
                    /* translators: 1: Post ID, 2: Title length */
                    $this->api->log_debug(sprintf(
                        __('Using Yoast SEO title for post %1$d (length: %2$d characters)', 'wp-bsky-autoposter'),
                        $post->ID,
                        strlen($yoast_title)
                    ));
                    $title = $yoast_title;
                }
            }
        }
        
        // Fall back to WordPress title
        if (empty($title)) {
            $title = get_the_title($post);
        }

        $this->post_data_cache[$post->ID]['title'] = $title;
        return $title;
    }

    /**
     * Get post excerpt with Yoast SEO priority if enabled.
     *
     * @since    1.5.0
     * @param    WP_Post $post The post object.
     * @return   string  The excerpt text.
     */
    private function get_post_excerpt($post) {
        if (isset($this->post_data_cache[$post->ID]['excerpt'])) {
            return $this->post_data_cache[$post->ID]['excerpt'];
        }

        $excerpt = '';
        $settings = get_option('wp_bsky_autoposter_settings');
        
        // Check if Yoast SEO metadata should be used
        if (!empty($settings['use_yoast_metadata']) && $this->is_yoast_seo_active()) {
            // Priority 1: Try to get Yoast SEO Twitter description first
            $yoast_twitter_description = get_post_meta($post->ID, '_yoast_wpseo_twitter-description', true);
            if (!empty($yoast_twitter_description)) {
                /* translators: 1: Post ID, 2: Description length */
                $this->api->log_debug(sprintf(
                    __('Using Yoast SEO Twitter description for post %1$d (length: %2$d characters)', 'wp-bsky-autoposter'),
                    $post->ID,
                    strlen($yoast_twitter_description)
                ));
                $excerpt = $yoast_twitter_description;
            }
            
            // Priority 2: Try to get Yoast SEO meta description
            if (empty($excerpt)) {
                $yoast_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
                if (!empty($yoast_description)) {
                    /* translators: 1: Post ID, 2: Description length */
                    $this->api->log_debug(sprintf(
                        __('Using Yoast SEO meta description for post %1$d (length: %2$d characters)', 'wp-bsky-autoposter'),
                        $post->ID,
                        strlen($yoast_description)
                    ));
                    $excerpt = $yoast_description;
                }
            }
        }
        
        // Fall back to WordPress excerpt
        if (empty($excerpt)) {
            $excerpt = get_the_excerpt($post);
        }

        $this->post_data_cache[$post->ID]['excerpt'] = $excerpt;
        return $excerpt;
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
     * Format the post message using the template.
     *
     * @since    1.0.0
     * @param    WP_Post $post     The post object.
     * @param    string  $template The message template.
     * @return   string  The formatted message.
     */
    private function format_post_message($post, $template) {
        // Get hashtags
        $hashtags = $this->get_hashtags($post->ID);

        // Get the post link with UTM parameters if enabled
        $link = $this->get_post_link_with_utm($post);

        // Get plugin settings
        $settings = get_option('wp_bsky_autoposter_settings');

        // Get excerpt with Yoast SEO priority or fallback text
        $excerpt = $this->get_post_excerpt($post);
        if (empty($excerpt) && !empty($settings['fallback_text'])) {
            // Process placeholders in fallback text
            $fallback_replacements = array(
                '{title}' => html_entity_decode($this->get_post_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                '{link}' => $link,
                '{hashtags}' => $hashtags,
            );
            $excerpt = str_replace(array_keys($fallback_replacements), array_values($fallback_replacements), $settings['fallback_text']);
        }

        // Truncate excerpt if needed
        $excerpt = $this->truncate_for_at_protocol($excerpt);

        $replacements = array(
            '{title}' => html_entity_decode($this->get_post_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            '{excerpt}' => $excerpt,
            '{link}' => $link,
            '{hashtags}' => $hashtags,
        );

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Ensure the final message doesn't exceed the limit
        return $this->truncate_for_at_protocol($message);
    }

    /**
     * Get post URL with Yoast SEO canonical priority if enabled.
     *
     * @since    1.5.0
     * @param    WP_Post $post The post object.
     * @return   string  The post URL.
     */
    private function get_post_url($post) {
        if (isset($this->post_data_cache[$post->ID]['url'])) {
            return $this->post_data_cache[$post->ID]['url'];
        }

        $url = '';
        $settings = get_option('wp_bsky_autoposter_settings');
        
        // Check if Yoast SEO metadata should be used
        if (!empty($settings['use_yoast_metadata']) && $this->is_yoast_seo_active()) {
            // Try to get Yoast SEO canonical URL first
            $yoast_canonical = get_post_meta($post->ID, '_yoast_wpseo_canonical', true);
            if (!empty($yoast_canonical)) {
                /* translators: 1: Post ID, 2: Canonical URL */
                $this->api->log_debug(sprintf(
                    __('Using Yoast SEO canonical URL for post %1$d: %2$s', 'wp-bsky-autoposter'),
                    $post->ID,
                    $yoast_canonical
                ));
                $url = $yoast_canonical;
            }
        }
        
        // Fall back to WordPress permalink
        if (empty($url)) {
            $url = get_permalink($post);
        }

        $this->post_data_cache[$post->ID]['url'] = $url;
        return $url;
    }

    /**
     * Get post link with UTM parameters if enabled.
     *
     * @since    1.0.0
     * @param    WP_Post $post The post object.
     * @return   string  The post URL with UTM parameters if enabled.
     */
    private function get_post_link_with_utm($post) {
        $link = $this->get_post_url($post);
        
        // Get plugin settings
        $settings = get_option('wp_bsky_autoposter_settings');

        // Replace host with base_url if set and valid
        if (!empty($settings['base_url']) && filter_var($settings['base_url'], FILTER_VALIDATE_URL)) {
            $parsed_link = wp_parse_url($link);
            $parsed_base = wp_parse_url($settings['base_url']);
            if (!empty($parsed_base['scheme']) && !empty($parsed_base['host'])) {
                // Build new URL: base_url + path + query + fragment from original link
                $new_link = $settings['base_url'];
                if (!empty($parsed_link['path'])) {
                    $new_link .= $parsed_link['path'];
                }
                if (!empty($parsed_link['query'])) {
                    $new_link .= '?' . $parsed_link['query'];
                }
                if (!empty($parsed_link['fragment'])) {
                    $new_link .= '#' . $parsed_link['fragment'];
                }
                $link = $new_link;
            }
        }
        
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
     * Get post featured image with Yoast SEO Twitter image priority if enabled.
     *
     * @since    1.5.0
     * @param    WP_Post $post The post object.
     * @return   string|null  The image URL or null if no image available.
     */
    private function get_post_featured_image($post) {
        if (array_key_exists('featured_image', $this->post_data_cache[$post->ID])) {
            return $this->post_data_cache[$post->ID]['featured_image'];
        }

        $image_url = null;
        $settings = get_option('wp_bsky_autoposter_settings');
        
        // Check if Yoast SEO metadata should be used
        if (!empty($settings['use_yoast_metadata']) && $this->is_yoast_seo_active()) {
            // Priority 1: Try to get Yoast SEO Twitter image first
            $yoast_twitter_image = get_post_meta($post->ID, '_yoast_wpseo_twitter-image', true);
            if (!empty($yoast_twitter_image)) {
                /* translators: 1: Post ID, 2: Image URL */
                $this->api->log_debug(sprintf(
                    __('Using Yoast SEO Twitter image for post %1$d: %2$s', 'wp-bsky-autoposter'),
                    $post->ID,
                    $yoast_twitter_image
                ));
                $image_url = $yoast_twitter_image;
            }
            
            // Priority 2: Try to get Yoast SEO Facebook Open Graph image
            if (empty($image_url)) {
                $yoast_facebook_image = get_post_meta($post->ID, '_yoast_wpseo_opengraph-image', true);
                if (!empty($yoast_facebook_image)) {
                    /* translators: 1: Post ID, 2: Image URL */
                    $this->api->log_debug(sprintf(
                        __('Using Yoast SEO Facebook Open Graph image for post %1$d: %2$s', 'wp-bsky-autoposter'),
                        $post->ID,
                        $yoast_facebook_image
                    ));
                    $image_url = $yoast_facebook_image;
                }
            }
        }
        
        // Fall back to WordPress featured image
        if (empty($image_url) && has_post_thumbnail($post)) {
            $image_url = get_the_post_thumbnail_url($post, 'large');
        }
        
        $this->post_data_cache[$post->ID]['featured_image'] = $image_url;
        return $image_url;
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

        // Get excerpt with Yoast SEO priority or fallback text
        $excerpt = $this->get_post_excerpt($post);
        if (empty($excerpt) && !empty($settings['fallback_text'])) {
            // Get hashtags and link for fallback text processing
            $hashtags = $this->get_hashtags($post->ID);
            $link = $this->get_post_link_with_utm($post);

            // Process placeholders in fallback text
            $fallback_replacements = array(
                '{title}' => html_entity_decode($this->get_post_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                '{link}' => $link,
                '{hashtags}' => $hashtags,
            );
            $excerpt = str_replace(array_keys($fallback_replacements), array_values($fallback_replacements), $settings['fallback_text']);
        }

        // Truncate excerpt for preview description
        $excerpt = $this->truncate_for_at_protocol($excerpt);

        // Get title with Yoast SEO priority and ensure proper encoding
        $title = html_entity_decode($this->get_post_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $preview_data = array(
            'uri' => $this->get_post_link_with_utm($post),
            'title' => $title,
            'description' => $excerpt,
        );

        // Get featured image with enhanced Yoast SEO priority
        $featured_image = $this->get_post_featured_image($post);
        if ($featured_image) {
            $preview_data['thumb'] = $featured_image;
        }

        return $preview_data;
    }

    /**
     * Get stock tickers from Yoast SEO News and convert to cashtags.
     *
     * @since    1.5.0
     * @param    int       $post_id    The WordPress post ID.
     * @return   string    The formatted cashtags string.
     */
    private function get_stock_cashtags($post_id) {
        if (isset($this->post_data_cache[$post_id]['cashtags'])) {
            return $this->post_data_cache[$post_id]['cashtags'];
        }

        $cashtags = '';
        $settings = get_option('wp_bsky_autoposter_settings');
        
        // Check if Yoast SEO metadata should be used
        if (!empty($settings['use_yoast_metadata']) && $this->is_yoast_seo_active()) {
            // Try to get Yoast SEO News stock tickers
            $stock_tickers = get_post_meta($post_id, '_yoast_wpseo_newssitemap-stocktickers', true);
            if (!empty($stock_tickers)) {
                // Parse the comma-separated list and extract tickers
                $tickers = array();
                $parts = array_map('trim', explode(',', $stock_tickers));
                
                foreach ($parts as $part) {
                    // Split by colon and get the ticker part (after the exchange)
                    $exchange_ticker = array_map('trim', explode(':', $part));
                    if (count($exchange_ticker) >= 2) {
                        $ticker = trim($exchange_ticker[1]);
                        // Only add if ticker is not empty and contains valid characters
                        if (!empty($ticker) && preg_match('/^[A-Z0-9.]+$/', $ticker)) {
                            $tickers[] = '$' . $ticker;
                        }
                    }
                }
                
                if (!empty($tickers)) {
                    $cashtags = implode(' ', $tickers);
                    /* translators: 1: Post ID, 2: Cashtags */
                    $this->api->log_debug(sprintf(
                        __('Using Yoast SEO News stock tickers for post %1$d: %2$s', 'wp-bsky-autoposter'),
                        $post_id,
                        $cashtags
                    ));
                }
            }
        }
        
        $this->post_data_cache[$post_id]['cashtags'] = $cashtags;
        return $cashtags;
    }

    /**
     * Get hashtags from post tags.
     *
     * @since    1.0.0
     * @param    int       $post_id    The WordPress post ID.
     * @return   string    The formatted hashtags string.
     */
    public function get_hashtags($post_id) {
        if (isset($this->post_data_cache[$post_id]['hashtags'])) {
            return $this->post_data_cache[$post_id]['hashtags'];
        }

        $tags = get_the_tags($post_id);
        $hashtags_array = array();
        
        // Get regular hashtags from post tags
        if ($tags) {
            foreach ($tags as $tag) {
                // Convert to lowercase and ensure proper formatting
                $tag_slug = strtolower($tag->slug);
                // Remove any special characters except hyphens
                $tag_slug = preg_replace('/[^a-z0-9-]/', '', $tag_slug);
                // Ensure the tag starts with a letter or number
                if (preg_match('/^[a-z0-9]/', $tag_slug)) {
                    $hashtags_array[] = '#' . $tag_slug;
                }
            }
        }
        
        // Get stock cashtags from Yoast SEO News
        $cashtags = $this->get_stock_cashtags($post_id);
        if (!empty($cashtags)) {
            $hashtags_array[] = $cashtags;
        }
        
        $hashtags_string = implode(' ', $hashtags_array);

        $this->post_data_cache[$post_id]['hashtags'] = $hashtags_string;
        return $hashtags_string;
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