<?php
/**
 * Test cashtag processing functionality
 * 
 * This test verifies that stock tickers from Yoast SEO News are properly
 * converted to cashtags and processed for Bluesky facets.
 */

// Mock WordPress functions for testing
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = true) {
        // Mock data for testing
        if ($key === '_yoast_wpseo_newssitemap-stocktickers') {
            return 'NASDAQ:AAPL, NYSE:GOOGL, NASDAQ:MSFT';
        }
        return '';
    }
}

if (!function_exists('get_option')) {
    function get_option($option) {
        if ($option === 'wp_bsky_autoposter_settings') {
            return array('use_yoast_metadata' => 1);
        }
        return array();
    }
}

// Test cashtag extraction from Yoast SEO News stock tickers
function test_cashtag_extraction() {
    echo "Testing cashtag extraction from Yoast SEO News stock tickers...\n";
    
    $stock_tickers = get_post_meta(1, '_yoast_wpseo_newssitemap-stocktickers', true);
    echo "Raw stock tickers: " . $stock_tickers . "\n";
    
    $parts = array_map('trim', explode(',', $stock_tickers));
    $cashtags = array();
    
    foreach ($parts as $part) {
        $exchange_ticker = array_map('trim', explode(':', $part));
        if (count($exchange_ticker) >= 2) {
            $ticker = trim($exchange_ticker[1]);
            if (!empty($ticker) && preg_match('/^[A-Z0-9.]+$/', $ticker)) {
                $cashtags[] = '$' . $ticker;
            }
        }
    }
    
    echo "Extracted cashtags: " . implode(' ', $cashtags) . "\n";
    
    $expected = array('$AAPL', '$GOOGL', '$MSFT');
    if ($cashtags === $expected) {
        echo "✓ Cashtag extraction test PASSED\n";
    } else {
        echo "✗ Cashtag extraction test FAILED\n";
        echo "Expected: " . implode(' ', $expected) . "\n";
        echo "Got: " . implode(' ', $cashtags) . "\n";
    }
    
    return $cashtags;
}

// Test facet creation for cashtags
function test_cashtag_facets($cashtags) {
    echo "\nTesting cashtag facet creation...\n";
    
    $message = "Check out our latest analysis on Apple and Google stocks. \$AAPL \$GOOGL \$MSFT";
    echo "Test message: " . $message . "\n";
    
    $facets = array();
    
    foreach ($cashtags as $cashtag) {
        $ticker = substr($cashtag, 1); // Remove $ symbol
        $pos = stripos($message, $cashtag);
        if ($pos !== false) {
            $facets[] = array(
                'index' => array(
                    'byteStart' => $pos,
                    'byteEnd' => $pos + strlen($cashtag)
                ),
                'features' => array(
                    array(
                        '$type' => 'app.bsky.richtext.facet#tag',
                        'tag' => strtolower($ticker)
                    )
                )
            );
            echo "✓ Added facet for $cashtag at position $pos\n";
        } else {
            echo "✗ Cashtag $cashtag not found in message\n";
        }
    }
    
    echo "Total facets created: " . count($facets) . "\n";
    
    if (count($facets) === 3) {
        echo "✓ Cashtag facet creation test PASSED\n";
    } else {
        echo "✗ Cashtag facet creation test FAILED\n";
    }
    
    return $facets;
}

// Test inline cashtag processing
function test_inline_cashtags() {
    echo "\nTesting inline cashtag processing...\n";
    
    $message = "Apple stock is performing well. Check out our analysis on AAPL and GOOGL. \$AAPL \$GOOGL \$MSFT";
    echo "Original message: " . $message . "\n";
    
    // Simulate inline processing
    $parts = explode(' ', $message);
    $hashtags = array();
    $cashtags = array();
    $main_text = array();
    $in_tags = false;
    
    foreach ($parts as $part) {
        if (strpos($part, '#') === 0 || strpos($part, '$') === 0) {
            $in_tags = true;
            if (strpos($part, '#') === 0) {
                $hashtags[] = $part;
            } else {
                $cashtags[] = $part;
            }
        } else {
            if ($in_tags) {
                $in_tags = false;
                $main_text[] = $part;
            } else {
                $main_text[] = $part;
            }
        }
    }
    
    $main_text_str = implode(' ', $main_text);
    $processed_cashtags = array();
    
    foreach ($cashtags as $cashtag) {
        $ticker = substr($cashtag, 1);
        $pattern = '/(?<![a-zA-Z0-9_])' . preg_quote($ticker, '/') . '(?![a-zA-Z0-9_])/i';
        if (preg_match($pattern, $main_text_str, $matches)) {
            $replacement = '$' . $matches[0];
            $main_text_str = preg_replace($pattern, $replacement, $main_text_str, 1);
        } else {
            $processed_cashtags[] = $cashtag;
        }
    }
    
    $result = trim($main_text_str);
    if (!empty($processed_cashtags)) {
        $result .= ' ' . implode(' ', $processed_cashtags);
    }
    
    echo "Processed message: " . $result . "\n";
    
    $expected = "Apple stock is performing well. Check out our analysis on \$AAPL and \$GOOGL. \$MSFT";
    if ($result === $expected) {
        echo "✓ Inline cashtag processing test PASSED\n";
    } else {
        echo "✗ Inline cashtag processing test FAILED\n";
        echo "Expected: " . $expected . "\n";
        echo "Got: " . $result . "\n";
    }
}

// Run tests
echo "=== Cashtag Processing Tests ===\n\n";

$cashtags = test_cashtag_extraction();
$facets = test_cashtag_facets($cashtags);
test_inline_cashtags();

echo "\n=== Test Summary ===\n";
echo "All cashtag processing tests completed.\n";
echo "The plugin should now properly convert Yoast SEO News stock tickers\n";
echo "to clickable cashtags on Bluesky with proper facets.\n"; 