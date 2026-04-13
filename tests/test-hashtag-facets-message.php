<?php
/**
 * CLI tests for hashtag facets parsed from post text (no full WordPress load).
 *
 * @package WP_BSky_AutoPoster
 */

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Minimal stub for CLI tests.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI stub only.
		return $default;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wp-bsky-autoposter-api.php';

$reflection = new ReflectionClass( 'WP_Bsky_AutoPoster_API' );
$parse_m    = $reflection->getMethod( 'parse_hashtag_facets_from_message' );
$parse_m->setAccessible( true );
$merge_m    = $reflection->getMethod( 'merge_facets_remove_overlaps' );
$merge_m->setAccessible( true );

$api = new WP_Bsky_AutoPoster_API();
$failed = 0;

/**
 * Record assertion result.
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

// Template-style message: literal #superyacht (not necessarily a WP tag).
$msg    = 'Giangrasso G24 Classic #giangrasso-group #palma-superyacht #superyacht';
$want   = strpos( $msg, '#superyacht' );
$facets = $parse_m->invoke( $api, $msg );
$found  = false;
foreach ( $facets as $f ) {
	if ( isset( $f['features'][0]['tag'] ) && 'superyacht' === $f['features'][0]['tag'] && $want === $f['index']['byteStart'] ) {
		$found = true;
		break;
	}
}
$assert( $found, 'Parse finds template literal #superyacht at correct byte offset' );

// UTF-8 prefix before ASCII hashtags.
$msg_utf8 = "Caffè giorno #foo #superyacht";
$pos_foo  = strpos( $msg_utf8, '#foo' );
$facets_u = $parse_m->invoke( $api, $msg_utf8 );
$foo_ok   = false;
foreach ( $facets_u as $f ) {
	if ( isset( $f['features'][0]['tag'] ) && 'foo' === $f['features'][0]['tag'] && $pos_foo === $f['index']['byteStart'] ) {
		$foo_ok = true;
		break;
	}
}
$assert( $foo_ok, 'UTF-8 prefix: #foo byte offset matches strpos' );

// Merge drops exact duplicates.
$dup_a = array(
	array(
		'index'    => array(
			'byteStart' => 10,
			'byteEnd'   => 22,
		),
		'features' => array(
			array(
				'$type' => 'app.bsky.richtext.facet#tag',
				'tag'   => 'superyacht',
			),
		),
	),
	array(
		'index'    => array(
			'byteStart' => 10,
			'byteEnd'   => 22,
		),
		'features' => array(
			array(
				'$type' => 'app.bsky.richtext.facet#tag',
				'tag'   => 'superyacht',
			),
		),
	),
);
$merged_dup = $merge_m->invoke( $api, $dup_a );
$assert( 1 === count( $merged_dup ), 'Merge drops exact duplicate facets' );

// Overlapping ranges: keep first in sort order (earlier start; longer wins on tie).
$ov = array(
	array(
		'index'    => array(
			'byteStart' => 5,
			'byteEnd'   => 12,
		),
		'features' => array(
			array(
				'$type' => 'app.bsky.richtext.facet#tag',
				'tag'   => 'b',
			),
		),
	),
	array(
		'index'    => array(
			'byteStart' => 0,
			'byteEnd'   => 20,
		),
		'features' => array(
			array(
				'$type' => 'app.bsky.richtext.facet#tag',
				'tag'   => 'a',
			),
		),
	),
);
$merged_ov = $merge_m->invoke( $api, $ov );
$assert( 1 === count( $merged_ov ), 'Merge keeps one facet when ranges overlap' );
$assert(
	0 === $merged_ov[0]['index']['byteStart'] && 20 === $merged_ov[0]['index']['byteEnd'],
	'Kept overlapping facet is the one sorted first (earlier byteStart)'
);

exit( $failed > 0 ? 1 : 0 );
