<?php
/**
 * CLI tests for fitting skeet text to Bluesky's post length (no full WordPress load).
 *
 * @package WP_BSky_AutoPoster
 */

$GLOBALS['bsky_settings'] = array(
	'log_level'        => 'error',
	'inline_hashtags'  => false,
);

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

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Settings stub.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- CLI stub.
		if ( 'wp_bsky_autoposter_settings' === $option ) {
			return $GLOBALS['bsky_settings'];
		}
		return $default;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wp-bsky-autoposter-api.php';

$api    = new WP_Bsky_AutoPoster_API();
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

/**
 * Grapheme length, matching the limit Bluesky enforces when intl is present.
 *
 * @param string $text Text.
 * @return int
 */
$graphemes = function ( $text ) {
	if ( function_exists( 'grapheme_strlen' ) ) {
		$length = grapheme_strlen( $text );
		if ( is_int( $length ) ) {
			return $length;
		}
	}
	return mb_strlen( $text, 'UTF-8' );
};

/**
 * Append trailing tags while both Bluesky limits hold. Independent of the plugin.
 *
 * @param string $body Body text.
 * @param array  $tags Tag tokens, earliest first.
 * @return string
 */
$fit_tags = function ( $body, $tags ) use ( $graphemes ) {
	$result = $body;
	foreach ( $tags as $tag ) {
		$candidate = ( '' === $result ) ? $tag : $result . ' ' . $tag;
		if ( $graphemes( $candidate ) > WP_Bsky_AutoPoster_API::MAX_POST_GRAPHEMES || strlen( $candidate ) > WP_Bsky_AutoPoster_API::MAX_POST_UTF8_BYTES ) {
			break;
		}
		$result = $candidate;
	}
	return $result;
};

$within = function ( $text ) use ( $graphemes ) {
	return $graphemes( $text ) <= WP_Bsky_AutoPoster_API::MAX_POST_GRAPHEMES
		&& strlen( $text ) <= WP_Bsky_AutoPoster_API::MAX_POST_UTF8_BYTES;
};

// Short text is unchanged.
$short = 'Hello #boats';
$assert( $short === $api->prepare_post_message( $short ), 'short post is unchanged' );

// Body under the limit, trailing tags dropped whole. A later short tag is not skipped ahead.
$body  = str_repeat( 'a', 290 );
$long  = '#' . str_repeat( 'b', 20 );
$msg   = $body . ' #ab ' . $long . ' #c';
$fitted = $api->prepare_post_message( $msg );
$assert( $fitted === $body . ' #ab', 'keeps the earliest tag that fits and drops the rest' );
$assert( false === strpos( $fitted, '...' ), 'dropping tags does not insert an ellipsis' );
$assert( false === strpos( $fitted, $long ), 'over-long tag is omitted whole' );
$assert( false === strpos( $fitted, '#c' ), 'a later short tag is not pulled forward' );
$assert( $within( $fitted ), 'kept prefix is within both limits' );

// The old cutter sliced inside the tag. The body stays intact when it already fits.
$sliced = str_repeat( 'a', 294 ) . ' #himmafushi';
$kept   = $api->prepare_post_message( $sliced );
$assert( $kept === str_repeat( 'a', 294 ), 'tag that does not fit is removed instead of sliced' );
$assert( false === strpos( $kept, '#himma' ), 'no partial hashtag remains' );

// Ellipsize only when the non-tag text itself is over the limit, then add tags that still fit.
$over_body = str_repeat( 'b', 350 ) . ' #keep #also';
$cut       = $api->prepare_post_message( $over_body );
$assert( $cut === str_repeat( 'b', 297 ) . '...', 'over-long body is ellipsized and tags do not fit after that' );
$assert( 300 === $graphemes( $cut ), 'ellipsized body is 300 graphemes' );

// Exactly 300 stays. One over, with no tags, gains an ellipsis.
$exact = str_repeat( 'c', 300 );
$assert( $exact === $api->prepare_post_message( $exact ), '300 graphemes stay intact' );
$assert( $exact === $api->truncate_to_bluesky_limit( $exact ), 'card truncation leaves 300 graphemes intact' );
$one_over = str_repeat( 'c', 301 );
$assert( str_repeat( 'c', 297 ) . '...' === $api->prepare_post_message( $one_over ), '301 graphemes are ellipsized' );

// A full body of 300 is kept even when extra tags would have forced the old 297-character cut.
$full_body = str_repeat( 'd', 300 ) . ' #tag';
$assert( str_repeat( 'd', 300 ) === $api->prepare_post_message( $full_body ), 'tags are dropped before the body is ellipsized' );

// Hashtag inside the sentence is not a trailing tag.
$sentence = 'See #boats today ' . str_repeat( 'e', 280 );
$with_tail = $sentence . ' #trailer';
$assert( $graphemes( $with_tail ) > 300, 'fixture is over the limit' );
$kept_sentence = $api->prepare_post_message( $with_tail );
$assert( $kept_sentence === $sentence, 'only the trailing tag is dropped' );
$assert( false !== strpos( $kept_sentence, '#boats' ), 'in-sentence hashtag stays' );

// Cashtags use the same trailing-token rule.
$cash = str_repeat( 'f', 290 ) . ' $AAPL $THISISAVERYLONGTICKERTHATWILLNOTFIT';
$assert( str_repeat( 'f', 290 ) . ' $AAPL' === $api->prepare_post_message( $cash ), 'cashtags are dropped whole from the end' );

// Second pass does not change a fitted post.
$assert( $fitted === $api->prepare_post_message( $fitted ), 'fitting twice is stable' );

// Combining marks count as one grapheme when intl is available.
if ( function_exists( 'grapheme_strlen' ) ) {
	$cluster = "e\u{0301}";
	$assert( 1 === grapheme_strlen( $cluster ), 'fixture cluster is one grapheme' );
	$clusters = str_repeat( $cluster, 301 );
	$assert( 602 === mb_strlen( $clusters, 'UTF-8' ), 'fixture is 602 code points' );
	$cluster_fit = $api->prepare_post_message( $clusters );
	$assert( 300 === grapheme_strlen( $cluster_fit ), '301 grapheme clusters ellipsize to 300 graphemes' );
	$assert( '...' === substr( $cluster_fit, -3 ), 'cluster ellipsis is present' );

	$emoji = "👨‍👩‍👧‍👦";
	if ( 1 === grapheme_strlen( $emoji ) && strlen( $emoji ) > 10 ) {
		$per_byte = strlen( $emoji );
		$exact_fit = (int) floor( WP_Bsky_AutoPoster_API::MAX_POST_UTF8_BYTES / $per_byte );
		$under_bytes = str_repeat( $emoji, $exact_fit );
		$assert( strlen( $under_bytes ) <= 3000 && grapheme_strlen( $under_bytes ) <= 300, 'emoji fixture under the byte cap' );
		$assert( $under_bytes === $api->prepare_post_message( $under_bytes ), 'text under both limits is kept, even with multi-byte graphemes' );

		$over_bytes = str_repeat( $emoji, $exact_fit + 1 );
		$assert( strlen( $over_bytes ) > 3000 && grapheme_strlen( $over_bytes ) <= 300, 'emoji fixture exceeds bytes before graphemes' );
		$emoji_fit = $api->prepare_post_message( $over_bytes );
		$assert( $within( $emoji_fit ), 'byte-overflow text is brought back within both limits' );
		$assert( '...' === substr( $emoji_fit, -3 ), 'byte overflow is ellipsized' );
		$assert( strlen( $emoji_fit ) <= 3000, 'ellipsized emoji text is within 3000 bytes' );
	}
}

// Nautica regression: 30 tags, inline #azimut, no mid-tag ellipsis.
$GLOBALS['bsky_settings']['inline_hashtags'] = true;

// A folded tag is part of the body. When that body is over the limit, ellipsize it
// instead of deleting the word that was turned into a tag.
$folded = str_repeat( 'a', 294 ) . ' boats #boats';
$folded_fit = $api->prepare_post_message( $folded );
$assert( 300 === $graphemes( $folded_fit ), 'over-long body with a folded tag is ellipsized to 300 graphemes' );
$assert( '...' === substr( $folded_fit, -3 ), 'over-long body with a folded tag keeps an ellipsis' );
$assert( str_repeat( 'a', 294 ) !== $folded_fit, 'the folded word is not deleted to dodge the limit' );
$title = 'Azimut rafforza la presenza in Medio Oriente e alle Maldive';
$slugs = array(
	'al-seef-marina',
	'arabia-saudita',
	'armatori',
	'assistenza-post-vendita',
	'azimut',
	'azimut-yachts',
	'charter',
	'dubai-harbour',
	'dubai-harbour-yacht-club',
	'dubai-international-boat-show-2026',
	'elite-yachts',
	'fly-53',
	'fly-62',
	'fly-82',
	'flybridge',
	'gulf',
	'himmafushi',
	'jeddah-yacht-club',
	'kuwait',
	'maldive',
	'mercato-nautico',
	'middle-east',
	'msheireb-downtown-doha',
	'nautica-di-lusso',
	'qatar',
	'seadeck-6',
	'superyacht',
	'yacht',
	'yacht-brokerage',
	'yachting',
);
$raw_tags = array();
foreach ( $slugs as $slug ) {
	$raw_tags[] = '#' . $slug;
}
$raw = $title . ' ' . implode( ' ', $raw_tags );
$assert( $graphemes( $raw ) > 300, 'nautica fixture exceeds 300 graphemes before fitting' );

$nautica = $api->prepare_post_message( $raw );

// Inline placement runs before the limit: a tag whose slug is a whole word in the
// title is folded in (case preserved) and is not also left at the end. Hyphenated
// slugs stay trailing. #maldive matches "Maldive", which the old mid-tag cut never
// reached because that tag had already been sliced off.
$expected_body = $title;
$trailing      = array();
foreach ( $slugs as $slug ) {
	$token = '#' . $slug;
	if ( preg_match( '/[^a-zA-Z0-9_]/', $slug ) ) {
		$trailing[] = $token;
		continue;
	}
	$pattern = '/(?<![a-zA-Z0-9_])' . preg_quote( $slug, '/' ) . '(?![a-zA-Z0-9_])/i';
	if ( preg_match( $pattern, $expected_body, $word_match ) ) {
		$expected_body = preg_replace( $pattern, '#' . $word_match[0], $expected_body, 1 );
	} else {
		$trailing[] = $token;
	}
}
$expected = $fit_tags( $expected_body, $trailing );

$assert( $nautica === $expected, 'nautica post keeps a prefix of whole tags after inline placement' );
$assert( $within( $nautica ), 'nautica post is within the Bluesky limit' );
$assert( 0 === strpos( $nautica, '#Azimut ' ), 'inlined #Azimut stays on the title' );
$assert( false !== strpos( $nautica, '#Maldive' ), 'inlined #Maldive stays in the title' );
$assert( false === strpos( $nautica, '...' ), 'nautica post has no ellipsis' );
$assert( false === strpos( $nautica, '#himma...' ), 'nautica post does not end on a sliced tag' );
$assert( false !== strpos( $nautica, '#himmafushi' ), 'himmafushi fits as a whole tag' );
$assert( 1 !== preg_match( '/(?:^|\s)#azimut(?:\s|$)/', $nautica ), 'standalone #azimut was folded into the title' );
$assert( 1 !== preg_match( '/(?:^|\s)#maldive(?:\s|$)/', $nautica ), 'standalone #maldive was folded into the title' );

$tokens     = preg_split( '/\s+/', $nautica );
$tag_tokens = array();
foreach ( $tokens as $token ) {
	if ( isset( $token[0] ) && '#' === $token[0] ) {
		$tag_tokens[] = $token;
	}
}
$only_whole = true;
foreach ( $tag_tokens as $token ) {
	$slug = strtolower( substr( $token, 1 ) );
	if ( ! in_array( $slug, $slugs, true ) ) {
		$only_whole = false;
		break;
	}
}
$assert( $only_whole, 'every hashtag in the nautica post is a complete tag' );

$reflection = new ReflectionClass( 'WP_Bsky_AutoPoster_API' );
$parse = $reflection->getMethod( 'parse_hashtag_facets_from_message' );
if ( PHP_VERSION_ID < 80100 ) {
	$parse->setAccessible( true );
}
$facets     = $parse->invoke( $api, $nautica );
$bad_facet  = false;
foreach ( $facets as $facet ) {
	$tag_name = isset( $facet['features'][0]['tag'] ) ? $facet['features'][0]['tag'] : '';
	if ( 'himma' === $tag_name ) {
		$bad_facet = true;
	}
}
$assert( ! $bad_facet, 'sliced #himma is not given a tag facet' );

// The next omitted tag must not fit, so we did not drop more than the limit requires.
$kept_trailing = array();
foreach ( $tag_tokens as $token ) {
	if ( in_array( $token, $trailing, true ) ) {
		$kept_trailing[] = $token;
	}
}
$assert( $kept_trailing === array_slice( $trailing, 0, count( $kept_trailing ) ), 'omitted tags are a suffix of the original list' );
if ( count( $kept_trailing ) < count( $trailing ) ) {
	$next = $trailing[ count( $kept_trailing ) ];
	$too_far = $nautica . ' ' . $next;
	$assert( ! $within( $too_far ), 'the first omitted tag does not fit' );
}

$GLOBALS['bsky_settings']['inline_hashtags'] = false;
$assert( $nautica === $api->prepare_post_message( $nautica ), 'fitted nautica text is stable when prepared again' );

exit( $failed > 0 ? 1 : 0 );
