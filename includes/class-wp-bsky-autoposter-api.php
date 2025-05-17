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
            $this->log_debug('Successfully refreshed authentication token');
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
     * Write a message to the log file.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    string    $type       The type of message (error/success/debug/warning).
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
     * Log a warning message.
     *
     * @since    1.2.0
     * @param    string    $message    The warning message.
     */
    private function log_warning($message) {
        $this->write_log($message, 'warning');
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

    /**
     * Log a debug message.
     *
     * @since    1.2.0
     * @param    string    $message    The debug message.
     */
    private function log_debug($message) {
        $this->write_log($message, 'debug');
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
            $this->log_warning('Failed to download image at ' . $image_url . ': ' . $response->get_error_message());
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
                $this->log_debug('Determined image type from extension: ' . $content_type);
            } else {
                $this->log_warning('Could not determine image type from extension: ' . $extension);
                return null;
            }
        }

        // Validate content type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml');
        if (!in_array($content_type, $allowed_types)) {
            $this->log_warning('Invalid image type: ' . $content_type);
            return null;
        }

        // Check image size (Bluesky limit is 976.56KB)
        $max_size = 976.56 * 1024; // Convert to bytes
        $image_size = strlen($image_data);
        
        if ($image_size > $max_size) {
            $this->log_debug(sprintf(
                'Image too large (%.2f MB), attempting to compress... Original size: %d bytes',
                $image_size / 1024 / 1024,
                $image_size
            ));
            
            // Create image resource
            $image = imagecreatefromstring($image_data);
            if (!$image) {
                $this->log_warning('Failed to create image resource for compression');
                return null;
            }

            // Calculate new dimensions while maintaining aspect ratio
            $width = imagesx($image);
            $height = imagesy($image);
            $ratio = $width / $height;
            
            $this->log_debug(sprintf(
                'Original dimensions: %dx%d pixels (ratio: %.2f)',
                $width,
                $height,
                $ratio
            ));
            
            // Start with 80% of original size
            $new_width = $width * 0.8;
            $new_height = $new_width / $ratio;
            
            $this->log_debug(sprintf(
                'New dimensions: %dx%d pixels (80%% of original)',
                $new_width,
                $new_height
            ));
            
            // Create new image
            $new_image = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG
            if ($content_type === 'image/png') {
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $this->log_debug('Preserving transparency for PNG image');
            }
            
            // Resize
            imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Output to buffer with compression
            ob_start();
            if ($content_type === 'image/jpeg') {
                imagejpeg($new_image, null, 85); // 85% quality
                $this->log_debug('Applied JPEG compression with 85% quality');
            } elseif ($content_type === 'image/png') {
                imagepng($new_image, null, 8); // Compression level 8
                $this->log_debug('Applied PNG compression with level 8');
            } elseif ($content_type === 'image/webp') {
                imagewebp($new_image, null, 85); // 85% quality
                $this->log_debug('Applied WebP compression with 85% quality');
            }
            $compressed_data = ob_get_clean();
            
            // Clean up
            imagedestroy($image);
            imagedestroy($new_image);
            
            $compressed_size = strlen($compressed_data);
            $size_reduction = (($image_size - $compressed_size) / $image_size) * 100;
            
            $this->log_debug(sprintf(
                'Compression results: %.2f MB -> %.2f MB (%.1f%% reduction)',
                $image_size / 1024 / 1024,
                $compressed_size / 1024 / 1024,
                $size_reduction
            ));
            
            // Check if compression was successful
            if ($compressed_size > $max_size) {
                $this->log_warning(sprintf(
                    'Image still too large after compression (%.2f MB > %.2f MB limit)',
                    $compressed_size / 1024 / 1024,
                    $max_size / 1024 / 1024
                ));
                return null;
            }
            
            $image_data = $compressed_data;
            $this->log_debug(sprintf(
                'Successfully compressed image to %.2f MB (%.1f%% of original size)',
                $compressed_size / 1024 / 1024,
                ($compressed_size / $image_size) * 100
            ));
        }

        // Upload to Bluesky
        $upload_response = $this->make_request('com.atproto.repo.uploadBlob', array(
            'headers' => array(
                'Content-Type' => $content_type,
            ),
            'body' => $image_data,
        ));

        if (is_wp_error($upload_response)) {
            $this->log_warning('Failed to upload image: ' . $upload_response->get_error_message());
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($upload_response), true);
        if (isset($body['blob'])) {
            // $this->log_success('Successfully uploaded image to Bluesky');
            return $body['blob'];
        }

        $this->log_warning('Failed to upload image: ' . wp_json_encode($body));
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
        $this->log_debug('Posting article ' . $post_id . ' at ' . $preview_data['uri']);

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
                $this->log_debug('Successfully uploaded image');
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
        $this->log_debug('Attempting to post with data: ' . wp_json_encode($post_data));

        // Retry logic for 5XX errors
        $max_retries = 3;
        $retry_delay = 30; // seconds
        $attempt = 0;

        while ($attempt < $max_retries) {
            $attempt++;
            
            // Send the post
            $response = $this->make_request('com.atproto.repo.createRecord', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode($post_data),
            ));

            if (is_wp_error($response)) {
                $this->log_error('Failed to post article ' . $post_id . ' to Bluesky: ' . $response->get_error_message());
                return false;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $response_code = wp_remote_retrieve_response_code($response);

            // Success case
            if (isset($body['uri'])) {
                $this->log_success('Successfully posted article ' . $post_id . ' to Bluesky: ' . $body['uri']);
                return true;
            }

            // Check if it's a 5XX error
            if ($response_code >= 500 && $response_code < 600) {
                if ($attempt < $max_retries) {
                    $this->log_error(sprintf(
                        'Received 5XX error (HTTP %d) on attempt %d/%d. Retrying in %d seconds...',
                        $response_code,
                        $attempt,
                        $max_retries,
                        $retry_delay
                    ));
                    sleep($retry_delay);
                    continue;
                }
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

        $this->log_error(sprintf(
            'Failed to post to Bluesky after %d attempts with 5XX errors',
            $max_retries
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
} 