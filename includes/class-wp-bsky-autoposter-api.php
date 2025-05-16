<?php
/**
 * The API-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_BSky_AutoPoster
 */

class WP_BSky_AutoPoster_API {

    /**
     * The Bluesky API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The Bluesky API endpoint.
     */
    private $api_endpoint = 'https://bsky.social/xrpc/';

    /**
     * The session data.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $session    The session data.
     */
    private $session = null;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Initialize session
        $this->session = get_option('wp_bsky_autoposter_session');
    }

    /**
     * Authenticate with Bluesky.
     *
     * @since    1.0.0
     * @param    string    $handle    The Bluesky handle.
     * @param    string    $password  The app password.
     * @return   bool      True if authentication was successful.
     */
    public function authenticate($handle, $password) {
        // Ensure handle doesn't have @ prefix
        $handle = ltrim($handle, '@');

        $response = wp_remote_post($this->api_endpoint . 'com.atproto.server.createSession', array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'identifier' => $handle,
                'password' => $password,
            )),
        ));

        if (is_wp_error($response)) {
            $this->log_error('Authentication failed: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['accessJwt'])) {
            $this->session = array(
                'accessJwt' => $body['accessJwt'],
                'refreshJwt' => $body['refreshJwt'],
                'handle' => $body['handle'],
                'did' => $body['did'],
            );
            update_option('wp_bsky_autoposter_session', $this->session);
            $this->log_success('Successfully authenticated with Bluesky as ' . $handle);
            return true;
        }

        $this->log_error('Authentication failed: Invalid response from Bluesky API');
        return false;
    }

    /**
     * Refresh the authentication token.
     *
     * @since    1.0.0
     * @return   bool    True if token refresh was successful.
     */
    private function refresh_token() {
        if (empty($this->session) || empty($this->session['refreshJwt'])) {
            return false;
        }

        $response = wp_remote_post($this->api_endpoint . 'com.atproto.server.refreshSession', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->session['refreshJwt'],
                'Content-Type' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            $this->log_error('Token refresh failed: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['accessJwt'])) {
            $this->session['accessJwt'] = $body['accessJwt'];
            $this->session['refreshJwt'] = $body['refreshJwt'];
            update_option('wp_bsky_autoposter_session', $this->session);
            $this->log_success('Successfully refreshed authentication token');
            return true;
        }

        $this->log_error('Token refresh failed: Invalid response from Bluesky API');
        return false;
    }

    /**
     * Make an authenticated request to the Bluesky API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $args        The request arguments.
     * @return   array|WP_Error    The response or WP_Error on failure.
     */
    private function make_request($endpoint, $args = array()) {
        if (empty($this->session)) {
            $settings = get_option('wp_bsky_autoposter_settings');
            if (!$this->authenticate($settings['bluesky_handle'], $settings['app_password'])) {
                return new WP_Error('auth_failed', 'Authentication failed');
            }
        }

        // Add authorization header
        $args['headers'] = array_merge(
            isset($args['headers']) ? $args['headers'] : array(),
            array('Authorization' => 'Bearer ' . $this->session['accessJwt'])
        );

        $response = wp_remote_post($this->api_endpoint . $endpoint, $args);

        // Check for expired token
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['error']) && $body['error'] === 'ExpiredToken') {
                // Try to refresh the token
                if ($this->refresh_token()) {
                    // Retry the request with the new token
                    $args['headers']['Authorization'] = 'Bearer ' . $this->session['accessJwt'];
                    $response = wp_remote_post($this->api_endpoint . $endpoint, $args);
                }
            }
        }

        return $response;
    }

    /**
     * Upload an image to Bluesky.
     *
     * @since    1.0.0
     * @param    string    $image_url    The URL of the image to upload.
     * @return   array|null              The image reference or null if upload failed.
     */
    private function upload_image($image_url) {
        // Download the image
        $response = wp_remote_get($image_url);
        if (is_wp_error($response)) {
            $this->log_error('Failed to download image: ' . $response->get_error_message());
            return null;
        }

        $image_data = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        // If no content-type is provided, try to determine it from the URL
        if (empty($content_type)) {
            $extension = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));
            $mime_types = array(
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml'
            );
            
            if (isset($mime_types[$extension])) {
                $content_type = $mime_types[$extension];
                $this->log_success('Determined image type from extension: ' . $content_type);
            } else {
                $this->log_error('Could not determine image type from extension: ' . $extension);
                return null;
            }
        }

        // Validate content type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml');
        if (!in_array($content_type, $allowed_types)) {
            $this->log_error('Invalid image type: ' . $content_type);
            return null;
        }

        // Upload to Bluesky
        $upload_response = $this->make_request('com.atproto.repo.uploadBlob', array(
            'headers' => array(
                'Content-Type' => $content_type,
            ),
            'body' => $image_data,
        ));

        if (is_wp_error($upload_response)) {
            $this->log_error('Failed to upload image to Bluesky: ' . $upload_response->get_error_message());
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($upload_response), true);
        if (isset($body['blob'])) {
            $this->log_success('Successfully uploaded image to Bluesky');
            return $body['blob'];
        }

        $this->log_error('Failed to upload image to Bluesky: ' . wp_json_encode($body));
        return null;
    }

    /**
     * Get hashtags from post tags.
     *
     * @since    1.0.0
     * @param    int       $post_id    The WordPress post ID.
     * @return   string    The formatted hashtags string.
     */
    public function get_hashtags($post_id) {
        $tags = get_the_tags($post_id);
        if (!$tags) {
            return '';
        }

        $hashtags = array();
        foreach ($tags as $tag) {
            // Convert to lowercase and ensure proper formatting
            $tag_slug = strtolower($tag->slug);
            // Remove any special characters except hyphens
            $tag_slug = preg_replace('/[^a-z0-9-]/', '', $tag_slug);
            // Ensure the tag starts with a letter or number
            if (preg_match('/^[a-z0-9]/', $tag_slug)) {
                $hashtags[] = '#' . $tag_slug;
            }
        }

        return implode(' ', $hashtags);
    }

    /**
     * Post to Bluesky.
     *
     * @since    1.0.0
     * @param    string    $message        The message to post.
     * @param    array     $preview_data   The preview data for the post.
     * @param    int       $post_id        The WordPress post ID.
     * @return   bool      True if the post was successful.
     */
    public function post_to_bluesky($message, $preview_data, $post_id) {
        $this->log_success('Posting article ' . $post_id);

        if (empty($this->session)) {
            $settings = get_option('wp_bsky_autoposter_settings');
            if (!$this->authenticate($settings['bluesky_handle'], $settings['app_password'])) {
                return false;
            }
        }

        // Upload image if available
        $image_ref = null;
        if (!empty($preview_data['thumb'])) {
            $image_ref = $this->upload_image($preview_data['thumb']);
            if ($image_ref) {
                $this->log_success('Successfully uploaded image for post: ' . $preview_data['uri']);
            }
        }

        // Get WordPress site language
        $site_language = get_locale();
        // Convert WordPress locale to ISO 639-1 language code
        $language_code = substr($site_language, 0, 2);

        // Prepare the post data
        $post_data = array(
            'repo' => $this->session['did'],
            'collection' => 'app.bsky.feed.post',
            'record' => array(
                '$type' => 'app.bsky.feed.post',
                'text' => $message,
                'createdAt' => gmdate('c'),
                'langs' => array($language_code),
            ),
        );

        // Add facets for hashtags
        $facets = array();
        $tags = get_the_tags($post_id);
        if ($tags) {
            foreach ($tags as $tag) {
                $tag_slug = strtolower($tag->slug);
                $hashtag = '#' . $tag_slug;
                $pos = strpos($message, $hashtag);
                if ($pos !== false) {
                    $facets[] = array(
                        'index' => array(
                            'byteStart' => $pos,
                            'byteEnd' => $pos + strlen($hashtag)
                        ),
                        'features' => array(
                            array(
                                '$type' => 'app.bsky.richtext.facet#tag',
                                'tag' => $tag_slug
                            )
                        )
                    );
                }
            }
        }
        if (!empty($facets)) {
            $post_data['record']['facets'] = $facets;
        }

        // Add embed if we have preview data
        if (!empty($preview_data['uri'])) {
            $embed = array(
                '$type' => 'app.bsky.embed.external',
                'external' => array(
                    '$type' => 'app.bsky.embed.external#external',
                    'uri' => $preview_data['uri'],
                    'title' => $preview_data['title'],
                    'description' => $preview_data['description'],
                ),
            );

            // Add image if available
            if ($image_ref) {
                $embed['external']['thumb'] = $image_ref;
            }

            $post_data['record']['embed'] = $embed;
        }

        // Log the post data for debugging
        $this->log_success('Attempting to post with data: ' . wp_json_encode($post_data));

        // Send the post
        $response = $this->make_request('com.atproto.repo.createRecord', array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($post_data),
        ));

        if (is_wp_error($response)) {
            $this->log_error('Failed to post to Bluesky: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $response_code = wp_remote_retrieve_response_code($response);

        if (isset($body['uri'])) {
            $this->log_success('Successfully posted to Bluesky: ' . $body['uri']);
            return true;
        }

        // Extract detailed error message
        $error_message = 'Unknown error';
        if (isset($body['error'])) {
            $error_message = $body['error'];
        } elseif (isset($body['message'])) {
            $error_message = $body['message'];
        }

        // Log detailed error information
        $this->log_error(sprintf(
            'Failed to post to Bluesky (HTTP %d): %s. Response: %s',
            $response_code,
            $error_message,
            wp_json_encode($body)
        ));

        return false;
    }

    /**
     * Get the log file path.
     *
     * @since    1.0.0
     * @return   string    The path to the log file.
     */
    private function get_log_file_path() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/wp-bsky-autoposter.log';
    }

    /**
     * Write a message to the log file.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    string    $type       The type of message (error/success).
     */
    private function write_log($message, $type = 'info') {
        $log_file = $this->get_log_file_path();
        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($type), $message);
        
        // Create the uploads directory if it doesn't exist
        wp_mkdir_p(dirname($log_file));
        
        // Write to the log file
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    /**
     * Log an error message.
     *
     * @since    1.0.0
     * @param    string    $message    The error message.
     */
    private function log_error($message) {
        $this->write_log($message, 'error');
    }

    /**
     * Log a success message.
     *
     * @since    1.0.0
     * @param    string    $message    The success message.
     */
    private function log_success($message) {
        $this->write_log($message, 'success');
    }
} 