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
     * Upload an image to Bluesky.
     *
     * @since    1.0.0
     * @param    string    $image_url    The URL of the image to upload.
     * @return   string|false    The blob reference if successful, false otherwise.
     */
    private function upload_image($image_url) {
        if (empty($image_url)) {
            return false;
        }

        // Download the image
        $image_data = wp_remote_get($image_url);
        if (is_wp_error($image_data)) {
            $this->log_error('Failed to download image: ' . $image_data->get_error_message());
            return false;
        }

        $image_content = wp_remote_retrieve_body($image_data);
        $image_type = wp_remote_retrieve_header($image_data, 'content-type');

        // Upload to Bluesky
        $response = wp_remote_post($this->api_endpoint . 'com.atproto.repo.uploadBlob', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->session['accessJwt'],
                'Content-Type' => $image_type,
            ),
            'body' => $image_content,
        ));

        if (is_wp_error($response)) {
            $this->log_error('Failed to upload image: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $response_code = wp_remote_retrieve_response_code($response);

        if (isset($body['blob']['ref']['$link'])) {
            return $body['blob']['ref']['$link'];
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
            'Failed to upload image (HTTP %d): %s. Response: %s',
            $response_code,
            $error_message,
            wp_json_encode($body)
        ));

        return false;
    }

    /**
     * Post to Bluesky.
     *
     * @since    1.0.0
     * @param    string    $message        The message to post.
     * @param    array     $preview_data   The preview data for the post.
     * @return   bool      True if the post was successful.
     */
    public function post_to_bluesky($message, $preview_data) {
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

        // Prepare the post data
        $post_data = array(
            'repo' => $this->session['did'],
            'collection' => 'app.bsky.feed.post',
            'record' => array(
                'text' => $message,
                'createdAt' => gmdate('c'),
                'langs' => array('en'),
            ),
        );

        // Add embed if we have preview data
        if (!empty($preview_data['uri'])) {
            $embed = array(
                'type' => 'app.bsky.embed.external',
                'external' => array(
                    'uri' => $preview_data['uri'],
                    'title' => $preview_data['title'],
                    'description' => $preview_data['description'],
                ),
            );

            // Add image if available
            if ($image_ref) {
                $embed['external']['thumb'] = array(
                    '$type' => 'blob',
                    'ref' => array(
                        '$link' => $image_ref,
                    ),
                    'mimeType' => 'image/jpeg',
                );
            }

            $post_data['record']['embed'] = $embed;
        }

        // Send the post
        $response = wp_remote_post($this->api_endpoint . 'com.atproto.repo.createRecord', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->session['accessJwt'],
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