<?php
/**
 * WordPress Abilities API integration.
 *
 * Registers discoverable abilities on WordPress 6.9 and newer. Older installs
 * keep the publish hook and admin AJAX handlers and skip this class's hooks.
 *
 * @since      1.8.0
 * @package    WP_BSky_AutoPoster
 */

/**
 * Registers Bluesky abilities with the Abilities API.
 *
 * @since 1.8.0
 */
class WP_BSky_AutoPoster_Abilities {

    /**
     * Ability category slug.
     *
     * @since 1.8.0
     * @var string
     */
    const CATEGORY = 'bluesky';

    /**
     * Plugin instance that performs preview, share, and connection checks.
     *
     * @since 1.8.0
     * @var WP_BSky_AutoPoster
     */
    private $plugin;

    /**
     * Hook registration when the Abilities API is available.
     *
     * @since 1.8.0
     * @param WP_BSky_AutoPoster $plugin Main plugin instance.
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;

        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }

        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    /**
     * Register the Bluesky ability category.
     *
     * @since 1.8.0
     */
    public function register_category() {
        wp_register_ability_category(
            self::CATEGORY,
            array(
                'label'       => __( 'Bluesky', 'wp-bsky-autoposter' ),
                'description' => __( 'Share WordPress posts to Bluesky.', 'wp-bsky-autoposter' ),
            )
        );
    }

    /**
     * Register preview, share, status, and connection abilities.
     *
     * @since 1.8.0
     */
    public function register_abilities() {
        $this->register_preview_post();
        $this->register_share_post();
        $this->register_get_status();
        $this->register_test_connection();
    }

    /**
     * Permission check for previewing a post.
     *
     * @since 1.8.0
     * @param mixed $input Ability input.
     * @return bool
     */
    public function user_can_edit_post( $input ) {
        $post_id = $this->input_post_id( $input );
        if ( $post_id <= 0 ) {
            return false;
        }

        return current_user_can( 'edit_post', $post_id );
    }

    /**
     * Permission check for sharing a post as the site Bluesky account.
     *
     * @since 1.8.0
     * @param mixed $input Ability input.
     * @return bool
     */
    public function user_can_share_post( $input ) {
        $post_id = $this->input_post_id( $input );
        if ( $post_id <= 0 ) {
            return false;
        }

        return current_user_can( 'edit_post', $post_id ) && current_user_can( 'publish_posts' );
    }

    /**
     * Permission check for settings-level abilities.
     *
     * @since 1.8.0
     * @return bool
     */
    public function user_can_manage_options() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Execute the preview ability.
     *
     * @since 1.8.0
     * @param array $input Ability input.
     * @return array|WP_Error
     */
    public function execute_preview_post( $input ) {
        return $this->plugin->get_share_preview( $this->input_post_id( $input ) );
    }

    /**
     * Execute the share ability.
     *
     * @since 1.8.0
     * @param array $input Ability input.
     * @return array|WP_Error
     */
    public function execute_share_post( $input ) {
        $force = ! empty( $input['force'] );
        return $this->plugin->share_post( $this->input_post_id( $input ), $force );
    }

    /**
     * Execute the status ability.
     *
     * @since 1.8.0
     * @return array
     */
    public function execute_get_status() {
        return $this->plugin->get_connection_status();
    }

    /**
     * Execute the stored-credential connection test.
     *
     * @since 1.8.0
     * @return array|WP_Error
     */
    public function execute_test_connection() {
        return $this->plugin->test_stored_connection();
    }

    /**
     * Register wp-bsky-autoposter/preview-post.
     *
     * @since 1.8.0
     */
    private function register_preview_post() {
        wp_register_ability(
            'wp-bsky-autoposter/preview-post',
            array(
                'label'               => __( 'Preview Bluesky Post', 'wp-bsky-autoposter' ),
                'description'         => __( 'Builds the Bluesky text and link card for a WordPress post without publishing anything to Bluesky.', 'wp-bsky-autoposter' ),
                'category'            => self::CATEGORY,
                'input_schema'        => $this->post_id_schema( false ),
                'output_schema'       => array(
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'required'             => array( 'message', 'uri', 'title', 'description', 'thumb', 'hashtags' ),
                    'properties'           => array(
                        'message'     => array(
                            'type'        => 'string',
                            'description' => __( 'The skeet text that would be posted.', 'wp-bsky-autoposter' ),
                        ),
                        'uri'         => array(
                            'type'        => 'string',
                            'description' => __( 'Link card URL, including UTM parameters when tracking is enabled.', 'wp-bsky-autoposter' ),
                        ),
                        'title'       => array(
                            'type'        => 'string',
                            'description' => __( 'Link card title.', 'wp-bsky-autoposter' ),
                        ),
                        'description' => array(
                            'type'        => 'string',
                            'description' => __( 'Link card description.', 'wp-bsky-autoposter' ),
                        ),
                        'thumb'       => array(
                            'type'        => array( 'string', 'null' ),
                            'description' => __( 'Link card image URL, or null when the post has no image.', 'wp-bsky-autoposter' ),
                        ),
                        'hashtags'    => array(
                            'type'        => 'string',
                            'description' => __( 'Hashtags and cashtags derived from the post.', 'wp-bsky-autoposter' ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_preview_post' ),
                'permission_callback' => array( $this, 'user_can_edit_post' ),
                'meta'                => $this->ability_meta( true, true ),
            )
        );
    }

    /**
     * Register wp-bsky-autoposter/share-post.
     *
     * @since 1.8.0
     */
    private function register_share_post() {
        wp_register_ability(
            'wp-bsky-autoposter/share-post',
            array(
                'label'               => __( 'Share Post to Bluesky', 'wp-bsky-autoposter' ),
                'description'         => __( 'Publishes one WordPress post to Bluesky using the App Password stored in the plugin settings. Does not accept a handle or password. Without force, a post that was already shared is refused.', 'wp-bsky-autoposter' ),
                'category'            => self::CATEGORY,
                'input_schema'        => $this->post_id_schema( true ),
                'output_schema'       => array(
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'required'             => array( 'success', 'uri', 'url' ),
                    'properties'           => array(
                        'success' => array(
                            'type'        => 'boolean',
                            'description' => __( 'Whether the Bluesky post was created.', 'wp-bsky-autoposter' ),
                        ),
                        'uri'     => array(
                            'type'        => 'string',
                            'description' => __( 'AT URI of the created Bluesky post.', 'wp-bsky-autoposter' ),
                        ),
                        'url'     => array(
                            'type'        => 'string',
                            'description' => __( 'Public Bluesky web URL, or an empty string when the handle cannot be resolved.', 'wp-bsky-autoposter' ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_share_post' ),
                'permission_callback' => array( $this, 'user_can_share_post' ),
                'meta'                => $this->ability_meta( false, false ),
            )
        );
    }

    /**
     * Register wp-bsky-autoposter/get-status.
     *
     * @since 1.8.0
     */
    private function register_get_status() {
        wp_register_ability(
            'wp-bsky-autoposter/get-status',
            array(
                'label'               => __( 'Get Bluesky Status', 'wp-bsky-autoposter' ),
                'description'         => __( 'Reports whether Bluesky credentials are stored and whether link tracking and Yoast metadata are enabled. Never returns the App Password.', 'wp-bsky-autoposter' ),
                'category'            => self::CATEGORY,
                'output_schema'       => array(
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'required'             => array( 'configured', 'handle', 'link_tracking', 'yoast_metadata' ),
                    'properties'           => array(
                        'configured'     => array(
                            'type'        => 'boolean',
                            'description' => __( 'Whether a handle and App Password are both stored.', 'wp-bsky-autoposter' ),
                        ),
                        'handle'         => array(
                            'type'        => 'string',
                            'description' => __( 'Stored Bluesky handle without a leading @, or an empty string.', 'wp-bsky-autoposter' ),
                        ),
                        'link_tracking'  => array(
                            'type'        => 'boolean',
                            'description' => __( 'Whether UTM link tracking is enabled.', 'wp-bsky-autoposter' ),
                        ),
                        'yoast_metadata' => array(
                            'type'        => 'boolean',
                            'description' => __( 'Whether Yoast SEO metadata is enabled.', 'wp-bsky-autoposter' ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_get_status' ),
                'permission_callback' => array( $this, 'user_can_manage_options' ),
                'meta'                => $this->ability_meta( true, true ),
            )
        );
    }

    /**
     * Register wp-bsky-autoposter/test-connection.
     *
     * @since 1.8.0
     */
    private function register_test_connection() {
        wp_register_ability(
            'wp-bsky-autoposter/test-connection',
            array(
                'label'               => __( 'Test Bluesky Connection', 'wp-bsky-autoposter' ),
                'description'         => __( 'Authenticates with Bluesky using the App Password stored in settings. Does not accept a password argument.', 'wp-bsky-autoposter' ),
                'category'            => self::CATEGORY,
                'output_schema'       => array(
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'required'             => array( 'success', 'handle' ),
                    'properties'           => array(
                        'success' => array(
                            'type'        => 'boolean',
                            'description' => __( 'Whether authentication succeeded.', 'wp-bsky-autoposter' ),
                        ),
                        'handle'  => array(
                            'type'        => 'string',
                            'description' => __( 'Bluesky handle that authenticated, without a leading @.', 'wp-bsky-autoposter' ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_test_connection' ),
                'permission_callback' => array( $this, 'user_can_manage_options' ),
                'meta'                => $this->ability_meta( false, true ),
            )
        );
    }

    /**
     * Input schema for abilities that take a post ID.
     *
     * @since 1.8.0
     * @param bool $include_force Whether to accept a force flag.
     * @return array
     */
    private function post_id_schema( $include_force ) {
        $properties = array(
            'post_id' => array(
                'type'        => 'integer',
                'minimum'     => 1,
                'description' => __( 'The WordPress post ID.', 'wp-bsky-autoposter' ),
            ),
        );
        $required = array( 'post_id' );

        if ( $include_force ) {
            $properties['force'] = array(
                'type'        => 'boolean',
                'description' => __( 'Share again even when this post was already shared. Defaults to false.', 'wp-bsky-autoposter' ),
            );
        }

        return array(
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => $required,
            'properties'           => $properties,
        );
    }

    /**
     * Ability meta shared by every registration.
     *
     * `public` is read by WordPress 7.1 and newer. `show_in_rest` is what
     * WordPress 6.9 uses to expose the ability on the REST API.
     *
     * @since 1.8.0
     * @param bool $readonly   Whether the ability leaves its environment unchanged.
     * @param bool $idempotent Whether repeating the call has no further effect.
     * @return array
     */
    private function ability_meta( $readonly, $idempotent ) {
        return array(
            'public'       => true,
            'show_in_rest' => true,
            'annotations'  => array(
                'readonly'    => $readonly,
                'destructive' => false,
                'idempotent'  => $idempotent,
            ),
        );
    }

    /**
     * Read a post ID from ability input.
     *
     * @since 1.8.0
     * @param mixed $input Ability input.
     * @return int
     */
    private function input_post_id( $input ) {
        if ( ! is_array( $input ) || ! isset( $input['post_id'] ) ) {
            return 0;
        }

        return (int) $input['post_id'];
    }
}
