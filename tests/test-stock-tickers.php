<?php

/**
 * Test script for Yoast SEO News stock ticker functionality
 * This simulates the get_stock_cashtags method behavior
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
                '_yoast_wpseo_newssitemap-stocktickers' => 'NASD:AAPL, NYSE:IBM'
            ),
            456 => array(
                '_yoast_wpseo_newssitemap-stocktickers' => 'NASDAQ:MSFT, NYSE:GOOGL, NASDAQ:TSLA'
            ),
            789 => array(
                '_yoast_wpseo_newssitemap-stocktickers' => 'BOM:500.325, NASDAQ:AMAT'
            ),
            999 => array(
                '_yoast_wpseo_newssitemap-stocktickers' => 'INVALID:FORMAT, NASD:AAPL, NYSE:IBM'
            ),
            111 => array(
                '_yoast_wpseo_newssitemap-stocktickers' => '' // Empty
            )
        );
        
        if (isset($mock_meta[$post_id][$key])) {
            return $mock_meta[$post_id][$key];
        }
        return '';
    }
}

// Mock Yoast SEO detection
if (!function_exists('YoastSEO')) {
    function YoastSEO() {
        return new stdClass();
    }
}

// Test function (simplified version of the actual method)
function get_stock_cashtags($post_id) {
    $settings = get_option('wp_bsky_autoposter_settings');
    
    // Check if Yoast SEO metadata should be used
    if (!empty($settings['use_yoast_metadata']) && is_yoast_seo_active()) {
        // Try to get Yoast SEO News stock tickers
        $stock_tickers = get_post_meta($post_id, '_yoast_wpseo_newssitemap-stocktickers', true);
        if (!empty($stock_tickers)) {
            // Parse the comma-separated list and extract tickers
            $tickers = array();
            $parts = array_map('trim', explode(',', $stock_tickers));
            
            foreach ($parts as $part) {
                // Split by colon and get the ticker part (after the exchange)
                $exchange_ticker = array_map('trim', explode(':', $part));
                if (count($exchange_ticker) >= 2) {
                    $ticker = trim($exchange_ticker[1]);
                    // Only add if ticker is not empty and contains valid characters
                    if (!empty($ticker) && preg_match('/^[A-Z0-9.]+$/', $ticker)) {
                        $tickers[] = '$' . $ticker;
                    }
                }
            }
            
            if (!empty($tickers)) {
                return implode(' ', $tickers);
            }
        }
    }
    
    return '';
}

function is_yoast_seo_active() {
    return function_exists('YoastSEO') || class_exists('WPSEO_Admin');
}

// Test cases
echo "Testing Yoast SEO News stock ticker functionality...\n\n";

// Test 1: Basic stock tickers
$post1 = 123;
$cashtags1 = get_stock_cashtags($post1);
echo "Test 1 - Basic stock tickers:\n";
echo "Input:    NASD:AAPL, NYSE:IBM\n";
echo "Expected: \$AAPL \$IBM\n";
echo "Got:      $cashtags1\n";
echo "Result:   " . ($cashtags1 === '$AAPL $IBM' ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Multiple stock tickers with different exchanges
$post2 = 456;
$cashtags2 = get_stock_cashtags($post2);
echo "Test 2 - Multiple stock tickers:\n";
echo "Input:    NASDAQ:MSFT, NYSE:GOOGL, NASDAQ:TSLA\n";
echo "Expected: \$MSFT \$GOOGL \$TSLA\n";
echo "Got:      $cashtags2\n";
echo "Result:   " . ($cashtags2 === '$MSFT $GOOGL $TSLA' ? 'PASS' : 'FAIL') . "\n\n";

// Test 3: Stock tickers with dots (like BOM:500.325)
$post3 = 789;
$cashtags3 = get_stock_cashtags($post3);
echo "Test 3 - Stock tickers with dots:\n";
echo "Input:    BOM:500.325, NASDAQ:AMAT\n";
echo "Expected: \$500.325 \$AMAT\n";
echo "Got:      $cashtags3\n";
echo "Result:   " . ($cashtags3 === '$500.325 $AMAT' ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Mixed valid and invalid formats
$post4 = 999;
$cashtags4 = get_stock_cashtags($post4);
echo "Test 4 - Mixed valid and invalid formats:\n";
echo "Input:    INVALID:FORMAT, NASD:AAPL, NYSE:IBM\n";
echo "Expected: \$AAPL \$IBM (invalid format should be ignored)\n";
echo "Got:      $cashtags4\n";
echo "Result:   " . ($cashtags4 === '$AAPL $IBM' ? 'PASS' : 'FAIL') . "\n\n";

// Test 5: Empty stock tickers
$post5 = 111;
$cashtags5 = get_stock_cashtags($post5);
echo "Test 5 - Empty stock tickers:\n";
echo "Input:    (empty)\n";
echo "Expected: (empty string)\n";
echo "Got:      '$cashtags5'\n";
echo "Result:   " . (empty($cashtags5) ? 'PASS' : 'FAIL') . "\n\n";

// Test 6: Post without stock ticker data
$post6 = 9999;
$cashtags6 = get_stock_cashtags($post6);
echo "Test 6 - Post without stock ticker data:\n";
echo "Input:    (no data)\n";
echo "Expected: (empty string)\n";
echo "Got:      '$cashtags6'\n";
echo "Result:   " . (empty($cashtags6) ? 'PASS' : 'FAIL') . "\n\n";

echo "Test completed!\n"; 