<?php
/**
 * CLI tests for Abilities API registration and share guards.
 *
 * @package WP_BSky_AutoPoster
 */

$failed = 0;

/**
 * Record an assertion result.
 *
 * @param bool   $ok    Whether the assertion passed.
 * @param string $label Description.
 */
$assert = function ( $ok, $label ) use ( &$failed ) {
	if ( ! $ok ) {
		echo 'FAIL: ' . $label . "\n";
		$failed++;
	} else {
		echo 'PASS: ' . $label . "\n";
	}
};

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- CLI stub.
		unset( $domain );
		return $text;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		public $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		public $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * WP_Error check stub.
	 *
	 * @param mixed $thing Value to test.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Minimal WP_Post stub.
	 */
	class WP_Post {
		/**
		 * Post ID.
		 *
		 * @var int
		 */
		public $ID = 0;

		/**
		 * Post date.
		 *
		 * @var string
		 */
		public $post_date = '';

		/**
		 * Modified date.
		 *
		 * @var string
		 */
		public $post_modified = '';

		/**
		 * Post slug.
		 *
		 * @var string
		 */
		public $post_name = '';
	}
}

$GLOBALS['bsky_test_hooks']   = array();
$GLOBALS['bsky_abilities']    = array();
$GLOBALS['bsky_categories']   = array();
$GLOBALS['bsky_caps']         = array();
$GLOBALS['bsky_options']      = array();
$GLOBALS['bsky_posts']        = array();
$GLOBALS['bsky_post_status']  = array();
$GLOBALS['bsky_post_meta']    = array();

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Record action callbacks.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Unused.
	 * @param int      $accepted_args Unused.
	 */
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $priority, $accepted_args );
		$GLOBALS['bsky_test_hooks'][ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Filter stub.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Unused.
	 * @param int      $accepted_args Unused.
	 */
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Capability stub.
	 *
	 * @param string $cap Capability.
	 * @param mixed  $arg Optional object ID.
	 * @return bool
	 */
	function current_user_can( $cap, $arg = null ) {
		if ( ! isset( $GLOBALS['bsky_caps'][ $cap ] ) ) {
			return false;
		}
		$rule = $GLOBALS['bsky_caps'][ $cap ];
		if ( is_array( $rule ) ) {
			return in_array( $arg, $rule, true );
		}
		return (bool) $rule;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Option stub.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI stub.
		if ( array_key_exists( $option, $GLOBALS['bsky_options'] ) ) {
			return $GLOBALS['bsky_options'][ $option ];
		}
		return $default;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Post stub.
	 *
	 * @param int $post_id Post ID.
	 * @return WP_Post|null
	 */
	function get_post( $post_id ) {
		return isset( $GLOBALS['bsky_posts'][ $post_id ] ) ? $GLOBALS['bsky_posts'][ $post_id ] : null;
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	/**
	 * Post status stub.
	 *
	 * @param int $post_id Post ID.
	 * @return string|false
	 */
	function get_post_status( $post_id ) {
		return isset( $GLOBALS['bsky_post_status'][ $post_id ] ) ? $GLOBALS['bsky_post_status'][ $post_id ] : false;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Post meta stub.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Unused.
	 * @return mixed
	 */
	function get_post_meta( $post_id, $key, $single = false ) {
		unset( $single );
		if ( isset( $GLOBALS['bsky_post_meta'][ $post_id ][ $key ] ) ) {
			return $GLOBALS['bsky_post_meta'][ $post_id ][ $key ];
		}
		return '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	/**
	 * Post meta write stub.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return bool
	 */
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['bsky_post_meta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
	/**
	 * Revision stub.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	function wp_is_post_revision( $post_id ) {
		unset( $post_id );
		return false;
	}
}

if ( ! function_exists( 'wp_is_post_autosave' ) ) {
	/**
	 * Autosave stub.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	function wp_is_post_autosave( $post_id ) {
		unset( $post_id );
		return false;
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Basename stub.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	function plugin_basename( $file ) {
		return $file;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * Permalink stub.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	function get_permalink( $post ) {
		return 'https://example.com/' . $post->post_name . '/';
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	/**
	 * Title stub.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	function get_the_title( $post ) {
		unset( $post );
		return 'Hello Title';
	}
}

if ( ! function_exists( 'get_the_excerpt' ) ) {
	/**
	 * Excerpt stub.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	function get_the_excerpt( $post ) {
		unset( $post );
		return 'Hello excerpt';
	}
}

if ( ! function_exists( 'get_the_tags' ) ) {
	/**
	 * Tags stub.
	 *
	 * @param int $post_id Post ID.
	 * @return false
	 */
	function get_the_tags( $post_id ) {
		unset( $post_id );
		return false;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Strip tags stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'has_post_thumbnail' ) ) {
	/**
	 * Thumbnail stub.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	function has_post_thumbnail( $post ) {
		unset( $post );
		return false;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON encode stub.
	 *
	 * @param mixed $data Data.
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * URL parse stub.
	 *
	 * @param string $url URL.
	 * @return array|false
	 */
	function wp_parse_url( $url ) {
		return parse_url( $url );
	}
}

/**
 * Plugin stand-in used while registering abilities.
 */
class Bsky_Abilities_Fake_Plugin {
	/**
	 * Preview calls.
	 *
	 * @var array
	 */
	public $preview_calls = array();

	/**
	 * Share calls.
	 *
	 * @var array
	 */
	public $share_calls = array();

	/**
	 * Preview a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_share_preview( $post_id ) {
		$this->preview_calls[] = $post_id;
		return array(
			'message'     => 'preview',
			'uri'         => 'https://example.com/',
			'title'       => 'Title',
			'description' => 'Excerpt',
			'thumb'       => null,
			'hashtags'    => '',
		);
	}

	/**
	 * Share a post.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $force   Force flag.
	 * @return WP_Error
	 */
	public function share_post( $post_id, $force = false ) {
		$this->share_calls[] = array( $post_id, $force );
		return new WP_Error( 'wp_bsky_missing_credentials', 'Bluesky credentials are not configured.' );
	}

	/**
	 * Status payload.
	 *
	 * @return array
	 */
	public function get_connection_status() {
		return array(
			'configured'     => false,
			'handle'         => '',
			'link_tracking'  => false,
			'yoast_metadata' => false,
		);
	}

	/**
	 * Connection test.
	 *
	 * @return WP_Error
	 */
	public function test_stored_connection() {
		return new WP_Error( 'wp_bsky_missing_credentials', 'Bluesky credentials are not configured.' );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wp-bsky-autoposter-abilities.php';

$fake = new Bsky_Abilities_Fake_Plugin();
new WP_BSky_AutoPoster_Abilities( $fake );
$assert(
	empty( $GLOBALS['bsky_test_hooks']['wp_abilities_api_init'] ),
	'Registration is skipped when wp_register_ability is missing'
);

if ( ! function_exists( 'wp_register_ability' ) ) {
	/**
	 * Ability registration stub.
	 *
	 * @param string $name Ability name.
	 * @param array  $args Ability args.
	 * @return string
	 */
	function wp_register_ability( $name, $args ) {
		$GLOBALS['bsky_abilities'][ $name ] = $args;
		return $name;
	}
}

if ( ! function_exists( 'wp_register_ability_category' ) ) {
	/**
	 * Category registration stub.
	 *
	 * @param string $slug Category slug.
	 * @param array  $args Category args.
	 * @return string
	 */
	function wp_register_ability_category( $slug, $args ) {
		$GLOBALS['bsky_categories'][ $slug ] = $args;
		return $slug;
	}
}

$fake = new Bsky_Abilities_Fake_Plugin();
new WP_BSky_AutoPoster_Abilities( $fake );

foreach ( array( 'wp_abilities_api_categories_init', 'wp_abilities_api_init' ) as $hook ) {
	foreach ( $GLOBALS['bsky_test_hooks'][ $hook ] as $callback ) {
		call_user_func( $callback );
	}
}

$assert( isset( $GLOBALS['bsky_categories']['bluesky'] ), 'Registers the bluesky category' );

$expected = array(
	'wp-bsky-autoposter/preview-post',
	'wp-bsky-autoposter/share-post',
	'wp-bsky-autoposter/get-status',
	'wp-bsky-autoposter/test-connection',
);
$assert( array_keys( $GLOBALS['bsky_abilities'] ) === $expected, 'Registers the four abilities' );

/**
 * Collect JSON Schema property names.
 *
 * @param mixed $schema Schema fragment.
 * @return array
 */
function bsky_property_names( $schema ) {
	$names = array();
	if ( ! is_array( $schema ) || empty( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
		return $names;
	}
	foreach ( $schema['properties'] as $name => $child ) {
		$names[] = $name;
		$names   = array_merge( $names, bsky_property_names( $child ) );
	}
	return $names;
}

foreach ( $GLOBALS['bsky_abilities'] as $name => $args ) {
	$assert( '__return_true' !== $args['permission_callback'], $name . ' does not use __return_true' );
	$assert( true === $args['meta']['show_in_rest'], $name . ' is shown in REST' );
	$assert( true === $args['meta']['public'], $name . ' is marked public' );
	$assert( false === $args['meta']['annotations']['destructive'], $name . ' is not destructive' );
	$assert( 'bluesky' === $args['category'], $name . ' is in the bluesky category' );

	$property_names = array();
	if ( isset( $args['input_schema'] ) ) {
		$property_names = array_merge( $property_names, bsky_property_names( $args['input_schema'] ) );
	}
	if ( isset( $args['output_schema'] ) ) {
		$property_names = array_merge( $property_names, bsky_property_names( $args['output_schema'] ) );
	}
	$assert(
		! in_array( 'app_password', $property_names, true ) && ! in_array( 'password', $property_names, true ),
		$name . ' schema does not accept or return a password'
	);
}

$preview = $GLOBALS['bsky_abilities']['wp-bsky-autoposter/preview-post'];
$assert( true === $preview['meta']['annotations']['readonly'], 'preview-post is readonly' );
$assert( true === $preview['meta']['annotations']['idempotent'], 'preview-post is idempotent' );

$share = $GLOBALS['bsky_abilities']['wp-bsky-autoposter/share-post'];
$assert( false === $share['meta']['annotations']['readonly'], 'share-post is not readonly' );
$assert( false === $share['meta']['annotations']['idempotent'], 'share-post is not idempotent' );
$assert(
	array( 'post_id', 'force' ) === array_keys( $share['input_schema']['properties'] ),
	'share-post input is only post_id and force'
);

$status = $GLOBALS['bsky_abilities']['wp-bsky-autoposter/get-status'];
$assert( true === $status['meta']['annotations']['readonly'], 'get-status is readonly' );
$assert( empty( $status['input_schema'] ), 'get-status takes no input' );

$connection = $GLOBALS['bsky_abilities']['wp-bsky-autoposter/test-connection'];
$assert( false === $connection['meta']['annotations']['readonly'], 'test-connection is not readonly' );
$assert( true === $connection['meta']['annotations']['idempotent'], 'test-connection is idempotent' );
$assert( empty( $connection['input_schema'] ), 'test-connection takes no input' );

$GLOBALS['bsky_caps'] = array(
	'edit_post'      => array( 5 ),
	'publish_posts'  => false,
	'manage_options' => false,
);
$assert( true === call_user_func( $preview['permission_callback'], array( 'post_id' => 5 ) ), 'preview allows edit_post' );
$assert( false === call_user_func( $preview['permission_callback'], array( 'post_id' => 9 ) ), 'preview denies other posts' );
$assert( false === call_user_func( $share['permission_callback'], array( 'post_id' => 5 ) ), 'share requires publish_posts' );

$GLOBALS['bsky_caps']['publish_posts'] = true;
$assert( true === call_user_func( $share['permission_callback'], array( 'post_id' => 5 ) ), 'share allows editors of that post' );
$assert( false === call_user_func( $status['permission_callback'] ), 'status requires manage_options' );

$GLOBALS['bsky_caps']['manage_options'] = true;
$assert( true === call_user_func( $status['permission_callback'] ), 'status allows manage_options' );
$assert( true === call_user_func( $connection['permission_callback'] ), 'test-connection allows manage_options' );

call_user_func( $preview['execute_callback'], array( 'post_id' => 5 ) );
$assert( array( 5 ) === $fake->preview_calls, 'preview-post delegates to get_share_preview' );

call_user_func( $share['execute_callback'], array( 'post_id' => 5 ) );
call_user_func( $share['execute_callback'], array( 'post_id' => 5, 'force' => true ) );
$assert(
	array( array( 5, false ), array( 5, true ) ) === $fake->share_calls,
	'share-post defaults force to false'
);

$status_result = call_user_func( $status['execute_callback'] );
$assert(
	false === strpos( wp_json_encode( $status_result ), 'app_password' ),
	'get-status result has no password field name'
);

if ( ! defined( 'WP_BSKY_AUTOPOSTER_PLUGIN_DIR' ) ) {
	define( 'WP_BSKY_AUTOPOSTER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/includes/class-wp-bsky-autoposter-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-wp-bsky-autoposter-api.php';
require_once dirname( __DIR__ ) . '/includes/class-wp-bsky-autoposter.php';

$secret = 'super-secret-app-password';
$GLOBALS['bsky_options']['wp_bsky_autoposter_settings'] = array(
	'bluesky_handle' => '@demo.bsky.social',
	'app_password'   => $secret,
	'post_template'  => '{title} - {excerpt}',
);

$plugin = new WP_BSky_AutoPoster();

$missing = $plugin->share_post( 404, false );
$assert( is_wp_error( $missing ) && 'wp_bsky_post_not_found' === $missing->get_error_code(), 'share rejects a missing post' );

$draft                = new WP_Post();
$draft->ID            = 7;
$draft->post_name     = 'draft';
$draft->post_date     = '2026-10-02 10:00:00';
$draft->post_modified = '2026-10-02 10:00:00';
$GLOBALS['bsky_posts'][7]       = $draft;
$GLOBALS['bsky_post_status'][7] = 'draft';
$not_published                  = $plugin->share_post( 7, false );
$assert(
	is_wp_error( $not_published ) && 'wp_bsky_post_not_published' === $not_published->get_error_code(),
	'share rejects an unpublished post'
);

$GLOBALS['bsky_options']['wp_bsky_autoposter_settings']['app_password'] = '';
$GLOBALS['bsky_post_status'][7] = 'publish';
$no_creds                       = $plugin->share_post( 7, false );
$assert(
	is_wp_error( $no_creds ) && 'wp_bsky_missing_credentials' === $no_creds->get_error_code(),
	'share refuses to run without credentials'
);
$assert( false === strpos( $no_creds->get_error_message(), $secret ), 'share error does not include the password' );

$stored = $plugin->test_stored_connection();
$assert(
	is_wp_error( $stored ) && 'wp_bsky_missing_credentials' === $stored->get_error_code(),
	'test-connection uses stored credentials and does not accept a password argument'
);

$GLOBALS['bsky_options']['wp_bsky_autoposter_settings']['app_password']       = $secret;
$GLOBALS['bsky_options']['wp_bsky_autoposter_settings']['enable_link_tracking'] = false;
$GLOBALS['bsky_options']['wp_bsky_autoposter_settings']['use_yoast_metadata']   = true;
$live_status = $plugin->get_connection_status();
$encoded     = wp_json_encode( $live_status );
$assert( true === $live_status['configured'], 'status reports configured when handle and password are stored' );
$assert( 'demo.bsky.social' === $live_status['handle'], 'status handle has no leading @' );
$assert( false === $live_status['link_tracking'], 'status reports link tracking' );
$assert( true === $live_status['yoast_metadata'], 'status reports Yoast metadata' );
$assert( false === strpos( $encoded, $secret ), 'status omits the app password' );
$assert( ! array_key_exists( 'app_password', $live_status ), 'status has no app_password key' );

$GLOBALS['bsky_post_meta'][7]['_wp_bsky_previous_status'] = 'publish';
$already = $plugin->share_post( 7, false );
$assert(
	is_wp_error( $already ) && 'wp_bsky_already_shared' === $already->get_error_code(),
	'share refuses an already shared post unless force is set'
);

$preview_result = $plugin->get_share_preview( 7 );
$assert(
	is_array( $preview_result ) && false !== strpos( $preview_result['message'], 'Hello Title' ),
	'preview returns the formatted message'
);
$assert( 'https://example.com/draft/' === $preview_result['uri'], 'preview returns the post URL' );
$assert( null === $preview_result['thumb'], 'preview thumb is null without an image' );

exit( $failed > 0 ? 1 : 0 );
