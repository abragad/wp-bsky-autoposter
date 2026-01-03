<?php

/**
 * Test script for Yoast SEO detection
 * Run this to verify the detection logic works
 */

function is_yoast_seo_active() {
    return function_exists('YoastSEO') || class_exists('WPSEO_Admin');
}

echo "Testing Yoast SEO detection...\n";
echo "Yoast SEO active: " . (is_yoast_seo_active() ? 'YES' : 'NO') . "\n";

// Test with mocked Yoast SEO
echo "\nTesting with mocked YoastSEO function...\n";
if (!function_exists('YoastSEO')) {
    function YoastSEO() {
        return new stdClass();
    }
}
echo "Yoast SEO active (after mock): " . (is_yoast_seo_active() ? 'YES' : 'NO') . "\n";

// Test with mocked WPSEO_Admin class
echo "\nTesting with mocked WPSEO_Admin class...\n";
if (!class_exists('WPSEO_Admin')) {
    class WPSEO_Admin {}
}
echo "Yoast SEO active (after class mock): " . (is_yoast_seo_active() ? 'YES' : 'NO') . "\n";

echo "\nTest completed!\n"; 