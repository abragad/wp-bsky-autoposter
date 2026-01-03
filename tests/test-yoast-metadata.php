<?php

/**
 * Test script for Yoast SEO metadata functionality
 * This simulates the get_post_excerpt method behavior
 */

// Mock WordPress functions for testing
if (!function_exists('get_option')) {
    function get_option($option_name) {
        $mock_settings = array(
            'wp_bsky_autoposter_settings' => array(
                'use_yoast_metadata' => 1
            )
        );
        return isset($mock_settings[$option_name]) ? $mock_settings[$option_name] : array();
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = true) {
        $mock_meta = array(
            123 => array(
                '_yoast_wpseo_metadesc' => 'This is a Yoast SEO meta description for testing purposes.',
                '_yoast_wpseo_title' => 'Yoast SEO Title',
                '_yoast_wpseo_canonical' => 'https://example.com/custom-canonical-url',
                '_yoast_wpseo_twitter-title' => 'Twitter-Specific Title for Social Media',
                '_yoast_wpseo_twitter-description' => 'Twitter-specific description optimized for social sharing',
                '_yoast_wpseo_twitter-image' => 'https://example.com/twitter-specific-image.jpg'
            ),
            456 => array(
                '_yoast_wpseo_metadesc' => '', // Empty Yoast description
                '_yoast_wpseo_title' => '',
                '_yoast_wpseo_canonical' => '', // Empty canonical URL
                '_yoast_wpseo_twitter-title' => '', // Empty Twitter title
                '_yoast_wpseo_twitter-description' => '', // Empty Twitter description
                '_yoast_wpseo_twitter-image' => '' // Empty Twitter image
            ),
            789 => array(
                '_yoast_wpseo_metadesc' => 'Regular Yoast description',
                '_yoast_wpseo_title' => 'Regular Yoast title',
                '_yoast_wpseo_canonical' => '',
                '_yoast_wpseo_twitter-title' => '', // No Twitter title, should fall back to regular Yoast
                '_yoast_wpseo_twitter-description' => '', // No Twitter description, should fall back to regular Yoast
                '_yoast_wpseo_twitter-image' => '' // No Twitter image, should fall back to featured image
            )
        );
        
        if (isset($mock_meta[$post_id][$key])) {
            return $mock_meta[$post_id][$key];
        }
        return '';
    }
}

if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt($post) {
        $mock_excerpts = array(
            123 => 'This is the WordPress excerpt for post 123.',
            456 => 'This is the WordPress excerpt for post 456.',
            789 => 'This is the WordPress excerpt for post 789.'
        );
        
        $post_id = is_object($post) ? $post->ID : $post;
        return isset($mock_excerpts[$post_id]) ? $mock_excerpts[$post_id] : '';
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post) {
        $mock_titles = array(
            123 => 'WordPress Title for post 123',
            456 => 'WordPress Title for post 456',
            789 => 'WordPress Title for post 789'
        );
        
        $post_id = is_object($post) ? $post->ID : $post;
        return isset($mock_titles[$post_id]) ? $mock_titles[$post_id] : '';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post) {
        $mock_permalinks = array(
            123 => 'https://example.com/post-123',
            456 => 'https://example.com/post-456',
            789 => 'https://example.com/post-789'
        );
        
        $post_id = is_object($post) ? $post->ID : $post;
        return isset($mock_permalinks[$post_id]) ? $mock_permalinks[$post_id] : '';
    }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail($post) {
        $mock_thumbnails = array(
            123 => true,
            456 => true,
            789 => false
        );
        
        $post_id = is_object($post) ? $post->ID : $post;
        return isset($mock_thumbnails[$post_id]) ? $mock_thumbnails[$post_id] : false;
    }
}

if (!function_exists('get_the_post_thumbnail_url')) {
    function get_the_post_thumbnail_url($post, $size = 'large') {
        $mock_thumbnail_urls = array(
            123 => 'https://example.com/featured-image-123.jpg',
            456 => 'https://example.com/featured-image-456.jpg'
        );
        
        $post_id = is_object($post) ? $post->ID : $post;
        return isset($mock_thumbnail_urls[$post_id]) ? $mock_thumbnail_urls[$post_id] : '';
    }
}

// Mock Yoast SEO detection
if (!function_exists('YoastSEO')) {
    function YoastSEO() {
        return new stdClass();
    }
}

// Test function (simplified version of the actual method)
function get_post_excerpt($post) {
    $settings = get_option('wp_bsky_autoposter_settings');
    
    // Check if Yoast SEO metadata should be used
    if (!empty($settings['use_yoast_metadata']) && is_yoast_seo_active()) {
        // Priority 1: Try to get Yoast SEO Twitter description first
        $yoast_twitter_description = get_post_meta($post->ID, '_yoast_wpseo_twitter-description', true);
        if (!empty($yoast_twitter_description)) {
            return $yoast_twitter_description;
        }
        
        // Priority 2: Try to get Yoast SEO meta description
        $yoast_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        if (!empty($yoast_description)) {
            return $yoast_description;
        }
    }
    
    // Fall back to WordPress excerpt
    return get_the_excerpt($post);
}

function get_post_title($post) {
    $settings = get_option('wp_bsky_autoposter_settings');
    
    // Check if Yoast SEO metadata should be used
    if (!empty($settings['use_yoast_metadata']) && is_yoast_seo_active()) {
        // Priority 1: Try to get Yoast SEO Twitter title first
        $yoast_twitter_title = get_post_meta($post->ID, '_yoast_wpseo_twitter-title', true);
        if (!empty($yoast_twitter_title)) {
            return $yoast_twitter_title;
        }
        
        // Priority 2: Try to get Yoast SEO title
        $yoast_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
        if (!empty($yoast_title)) {
            return $yoast_title;
        }
    }
    
    // Fall back to WordPress title
    return get_the_title($post);
}

function get_post_url($post) {
    $settings = get_option('wp_bsky_autoposter_settings');
    
    // Check if Yoast SEO metadata should be used
    if (!empty($settings['use_yoast_metadata']) && is_yoast_seo_active()) {
        // Try to get Yoast SEO canonical URL first
        $yoast_canonical = get_post_meta($post->ID, '_yoast_wpseo_canonical', true);
        if (!empty($yoast_canonical)) {
            return $yoast_canonical;
        }
    }
    
    // Fall back to WordPress permalink
    return get_permalink($post);
}

function get_post_featured_image($post) {
    $settings = get_option('wp_bsky_autoposter_settings');
    
    // Check if Yoast SEO metadata should be used
    if (!empty($settings['use_yoast_metadata']) && is_yoast_seo_active()) {
        // Priority 1: Try to get Yoast SEO Twitter image first
        $yoast_twitter_image = get_post_meta($post->ID, '_yoast_wpseo_twitter-image', true);
        if (!empty($yoast_twitter_image)) {
            return $yoast_twitter_image;
        }
    }
    
    // Fall back to WordPress featured image
    if (has_post_thumbnail($post)) {
        return get_the_post_thumbnail_url($post, 'large');
    }
    
    return null;
}

function is_yoast_seo_active() {
    return function_exists('YoastSEO') || class_exists('WPSEO_Admin');
}

// Test cases
echo "Testing Yoast SEO metadata functionality...\n\n";

// Test 1: Post with Yoast SEO description
$post1 = (object) array('ID' => 123);
$excerpt1 = get_post_excerpt($post1);
echo "Test 1 - Post with Yoast SEO description:\n";
echo "Expected: This is a Yoast SEO meta description for testing purposes.\n";
echo "Got:      $excerpt1\n";
echo "Result:   " . (strpos($excerpt1, 'Yoast SEO meta description') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Post with empty Yoast SEO description
$post2 = (object) array('ID' => 456);
$excerpt2 = get_post_excerpt($post2);
echo "Test 2 - Post with empty Yoast SEO description:\n";
echo "Expected: This is the WordPress excerpt for post 456.\n";
echo "Got:      $excerpt2\n";
echo "Result:   " . (strpos($excerpt2, 'WordPress excerpt') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 3: Post without Yoast SEO data
$post3 = (object) array('ID' => 789);
$excerpt3 = get_post_excerpt($post3);
echo "Test 3 - Post without Yoast SEO data:\n";
echo "Expected: This is the WordPress excerpt for post 789.\n";
echo "Got:      $excerpt3\n";
echo "Result:   " . (strpos($excerpt3, 'WordPress excerpt') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Yoast SEO detection
echo "Test 4 - Yoast SEO detection:\n";
echo "Yoast SEO active: " . (is_yoast_seo_active() ? 'YES' : 'NO') . "\n";
echo "Result:   " . (is_yoast_seo_active() ? 'PASS' : 'FAIL') . "\n\n";

// Test 5: Post with Yoast SEO title
$title1 = get_post_title($post1);
echo "Test 5 - Post with Yoast SEO title:\n";
echo "Expected: Yoast SEO Title\n";
echo "Got:      $title1\n";
echo "Result:   " . (strpos($title1, 'Yoast SEO Title') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 6: Post with empty Yoast SEO title
$title2 = get_post_title($post2);
echo "Test 6 - Post with empty Yoast SEO title:\n";
echo "Expected: WordPress Title for post 456\n";
echo "Got:      $title2\n";
echo "Result:   " . (strpos($title2, 'WordPress Title') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 7: Post with Yoast SEO canonical URL
$url1 = get_post_url($post1);
echo "Test 7 - Post with Yoast SEO canonical URL:\n";
echo "Expected: https://example.com/custom-canonical-url\n";
echo "Got:      $url1\n";
echo "Result:   " . (strpos($url1, 'https://example.com/custom-canonical-url') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 8: Post without Yoast SEO canonical URL
$url2 = get_post_url($post2);
echo "Test 8 - Post without Yoast SEO canonical URL:\n";
echo "Expected: https://example.com/post-456\n";
echo "Got:      $url2\n";
echo "Result:   " . (strpos($url2, 'https://example.com/post-456') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 9: Post with Twitter-specific Yoast SEO metadata (highest priority)
$post4 = (object) array('ID' => 123);
$twitter_title = get_post_title($post4);
$twitter_description = get_post_excerpt($post4);
$twitter_image = get_post_featured_image($post4);

echo "Test 9 - Post with Twitter-specific Yoast SEO metadata:\n";
echo "Twitter Title:      $twitter_title\n";
echo "Expected:           Twitter-Specific Title for Social Media\n";
echo "Result:             " . (strpos($twitter_title, 'Twitter-Specific Title') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "Twitter Description: $twitter_description\n";
echo "Expected:            Twitter-specific description optimized for social sharing\n";
echo "Result:              " . (strpos($twitter_description, 'Twitter-specific description') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "Twitter Image:       $twitter_image\n";
echo "Expected:            https://example.com/twitter-specific-image.jpg\n";
echo "Result:              " . (strpos($twitter_image, 'twitter-specific-image.jpg') !== false ? 'PASS' : 'FAIL') . "\n\n";

// Test 10: Post with regular Yoast SEO metadata (fallback when Twitter is empty)
$post5 = (object) array('ID' => 789);
$regular_title = get_post_title($post5);
$regular_description = get_post_excerpt($post5);
$regular_image = get_post_featured_image($post5);

echo "Test 10 - Post with regular Yoast SEO metadata (Twitter empty):\n";
echo "Regular Title:       $regular_title\n";
echo "Expected:            Regular Yoast title\n";
echo "Result:              " . (strpos($regular_title, 'Regular Yoast title') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "Regular Description: $regular_description\n";
echo "Expected:            Regular Yoast description\n";
echo "Result:              " . (strpos($regular_description, 'Regular Yoast description') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "Regular Image:       $regular_image\n";
echo "Expected:            (null - no featured image)\n";
echo "Result:              " . ($regular_image === null ? 'PASS' : 'FAIL') . "\n\n";

// Test 11: Post with WordPress fallback (no Yoast metadata)
$post6 = (object) array('ID' => 456);
$wp_title = get_post_title($post6);
$wp_description = get_post_excerpt($post6);
$wp_image = get_post_featured_image($post6);

echo "Test 11 - Post with WordPress fallback (no Yoast metadata):\n";
echo "WP Title:            $wp_title\n";
echo "Expected:            WordPress Title for post 456\n";
echo "Result:              " . (strpos($wp_title, 'WordPress Title') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "WP Description:      $wp_description\n";
echo "Expected:            This is the WordPress excerpt for post 456.\n";
echo "Result:              " . (strpos($wp_description, 'WordPress excerpt') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "WP Image:            $wp_image\n";
echo "Expected:            https://example.com/featured-image-456.jpg\n";
echo "Result:              " . (strpos($wp_image, 'featured-image-456.jpg') !== false ? 'PASS' : 'FAIL') . "\n\n";

echo "Test completed!\n"; 