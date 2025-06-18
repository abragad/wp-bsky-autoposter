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
                '_yoast_wpseo_title' => 'Yoast SEO Title'
            ),
            456 => array(
                '_yoast_wpseo_metadesc' => '', // Empty Yoast description
                '_yoast_wpseo_title' => ''
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
        // Try to get Yoast SEO meta description first
        $yoast_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        if (!empty($yoast_description)) {
            return $yoast_description;
        }
    }
    
    // Fall back to WordPress excerpt
    return get_the_excerpt($post);
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

echo "Test completed!\n"; 