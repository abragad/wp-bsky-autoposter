# Yoast SEO Integration

The WP Bsky AutoPoster plugin includes comprehensive integration with Yoast SEO to enhance social media posting with optimized metadata. This integration allows the plugin to use Yoast SEO's carefully crafted social media and SEO metadata instead of default WordPress content.

## Overview

When Yoast SEO is active and the integration is enabled, the plugin will automatically detect and use Yoast SEO metadata fields with a priority system that ensures the best possible content for Bluesky posts.

## Features

### 1. Automatic Detection

The plugin automatically detects if Yoast SEO is active on your WordPress site:

```php
private function is_yoast_seo_active() {
    return function_exists('YoastSEO') || class_exists('WPSEO_Admin');
}
```

This method is used throughout the plugin to conditionally enable Yoast SEO features and is checked both in the settings page (to show/hide the Yoast SEO section) and when processing posts (to use Yoast SEO metadata).

### 2. Settings Integration

A dedicated "Yoast SEO Metadata" section appears in the plugin settings when Yoast SEO is active:

- **Use Yoast SEO Metadata**: Checkbox to enable/disable Yoast SEO integration
- **Automatic detection**: Settings section only appears when Yoast SEO is detected

### 3. Title Optimization

The plugin uses a priority system for post titles:

#### Priority Order:
1. **Yoast SEO Twitter Title** (`_yoast_wpseo_twitter-title`)
   - Specifically optimized for social media sharing
   - Usually shorter and more engaging than regular titles
   
2. **Yoast SEO Title** (`_yoast_wpseo_title`)
   - General SEO-optimized title
   - Fallback when Twitter title is not available
   
3. **WordPress Default Title** (`get_the_title()`)
   - Final fallback when no Yoast SEO titles are available

#### Example:
```php
// Priority 1: Twitter-specific title
$twitter_title = get_post_meta($post->ID, '_yoast_wpseo_twitter-title', true);
if (!empty($twitter_title)) {
    return $twitter_title;
}

// Priority 2: General SEO title
$yoast_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
if (!empty($yoast_title)) {
    return $yoast_title;
}

// Priority 3: WordPress title
return get_the_title($post);
```

### 4. Description/Excerpt Optimization

The plugin uses a priority system for post descriptions:

#### Priority Order:
1. **Yoast SEO Twitter Description** (`_yoast_wpseo_twitter-description`)
   - Optimized for social media character limits
   - Designed for maximum engagement
   
2. **Yoast SEO Meta Description** (`_yoast_wpseo_metadesc`)
   - General SEO-optimized description
   - Fallback when Twitter description is not available
   
3. **WordPress Excerpt** (`get_the_excerpt()`)
   - Final fallback when no Yoast SEO descriptions are available

### 5. Image Optimization

The plugin uses a priority system for featured images:

#### Priority Order:
1. **Yoast SEO Twitter Image** (`_yoast_wpseo_twitter-image`)
   - Specifically optimized for social media platforms
   - Often higher quality and better aspect ratios
   
2. **Yoast SEO Facebook Open Graph Image** (`_yoast_wpseo_opengraph-image`)
   - Optimized for Facebook and other Open Graph platforms
   - Fallback when Twitter image is not available
   
3. **WordPress Featured Image** (`get_the_post_thumbnail_url()`)
   - Final fallback when no Yoast SEO images are available

### 6. URL Optimization

The plugin can use Yoast SEO's canonical URLs:

#### Priority Order:
1. **Yoast SEO Canonical URL** (`_yoast_wpseo_canonical`)
   - Ensures the correct URL is used for social sharing
   - Useful for sites with multiple URL structures
   
2. **WordPress Permalink** (`get_permalink()`)
   - Fallback when no canonical URL is set

### 7. Stock Ticker Integration (Yoast SEO News)

For sites using Yoast SEO News plugin, the plugin automatically converts stock tickers to cashtags:

#### Supported Format:
- **Input**: `NASD:AAPL, NYSE:IBM, NASDAQ:MSFT`
- **Output**: `$AAPL $IBM $MSFT`

#### Integration:
- Cashtags are automatically added to the hashtags string and included in the post message
- Cashtags are converted to clickable facets on Bluesky for better engagement
- If inline hashtags are enabled, cashtags can be moved into the main text when the ticker symbol appears as a whole word

#### Implementation:
```php
$stock_tickers = get_post_meta($post_id, '_yoast_wpseo_newssitemap-stocktickers', true);
if (!empty($stock_tickers)) {
    // Parse and convert to cashtags
    $tickers = array();
    $parts = array_map('trim', explode(',', $stock_tickers));
    
    foreach ($parts as $part) {
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
```

## Configuration

### Enabling Yoast SEO Integration

1. Ensure Yoast SEO plugin is installed and activated
2. Go to **Settings > Bluesky AutoPoster**
3. Check the **"Use Yoast SEO Metadata"** option in the Yoast SEO section
4. Save settings

### Settings Location

The Yoast SEO settings appear in a dedicated section:
- **Section**: "Yoast SEO Metadata"
- **Field**: "Use Yoast SEO Metadata"
- **Description**: "If activated, we will check for post excerpt and other information in Yoast SEO metadata."
- **Metadata Fields Used**: When enabled, the plugin checks for:
  - Post titles (Twitter title, SEO title)
  - Post descriptions/excerpts (Twitter description, meta description)
  - Featured images (Twitter image, Open Graph image)
  - Canonical URLs
  - Stock tickers (converted to cashtags)

## Debug Logging

The plugin includes comprehensive debug logging for Yoast SEO integration:

### Title Logging
```
Using Yoast SEO Twitter title for post 123 (length: 45 characters)
Using Yoast SEO title for post 123 (length: 52 characters)
```

### Description Logging
```
Using Yoast SEO Twitter description for post 123 (length: 120 characters)
Using Yoast SEO meta description for post 123 (length: 156 characters)
```

### Image Logging
```
Using Yoast SEO Twitter image for post 123: https://example.com/twitter-image.jpg
Using Yoast SEO Facebook Open Graph image for post 123: https://example.com/facebook-image.jpg
```

### URL Logging
```
Using Yoast SEO canonical URL for post 123: https://example.com/custom-canonical-url
```

### Stock Ticker Logging
```
Using Yoast SEO News stock tickers for post 123: $AAPL $IBM $MSFT
Added cashtag facet for post 123: $AAPL at position 45
```

## Benefits

### 1. Better Social Media Optimization
- Uses content specifically crafted for social media platforms
- Optimized character counts and engagement
- Better image quality and aspect ratios

### 2. SEO Consistency
- Maintains consistency between SEO and social media content
- Uses the same optimized titles and descriptions
- Leverages Yoast SEO's content analysis

### 3. Professional Content
- Uses professionally crafted social media content
- Better hashtag integration with stock tickers
- Improved content quality and engagement

### 4. Flexibility
- Graceful fallback to WordPress defaults
- No disruption if Yoast SEO is disabled
- Maintains backward compatibility

## Technical Implementation

### Detection Method
```php
private function is_yoast_seo_active() {
    return function_exists('YoastSEO') || class_exists('WPSEO_Admin');
}
```

### Settings Integration
```php
// Add Yoast SEO section only if Yoast SEO is active
if ($this->is_yoast_seo_active()) {
    add_settings_section(
        'wp_bsky_autoposter_yoast',
        __('Yoast SEO Metadata', 'wp-bsky-autoposter'),
        array($this, 'yoast_section_callback'),
        $this->plugin_name
    );
}
```

### Priority System
All Yoast SEO fields follow a consistent priority system:
1. Platform-specific fields (Twitter, Facebook)
2. General SEO fields
3. WordPress default fields

### Caching
The plugin implements a caching mechanism to avoid redundant database calls:
- Post data (title, excerpt, URL, image, hashtags, cashtags) is cached per post ID
- Cache is cleared when a new post is published
- This improves performance when processing multiple metadata fields for the same post

## Compatibility

### WordPress Versions
- Compatible with WordPress 5.0+
- Tested with WordPress 6.x

### Yoast SEO Versions
- Compatible with Yoast SEO 14.0+
- Tested with latest versions

### PHP Versions
- Requires PHP 7.2+
- Tested with PHP 7.4, 8.0, 8.1, 8.2

## Troubleshooting

### Yoast SEO Not Detected
1. Ensure Yoast SEO plugin is activated
2. Check if Yoast SEO functions are available
3. Verify plugin compatibility

### No Yoast SEO Settings
1. Yoast SEO must be active for settings to appear
2. Settings section is automatically hidden when Yoast SEO is inactive

### Debug Information
Enable debug logging to see which Yoast SEO fields are being used:
1. Go to **Settings > Bluesky AutoPoster**
2. Set **Log Level** to "Debug"
3. Check the log viewer for detailed information

## Future Enhancements

Potential future Yoast SEO integrations:

### 1. Facebook Open Graph Fields
- `_yoast_wpseo_opengraph-title`
- `_yoast_wpseo_opengraph-description`

### 2. Content Analysis
- `_yoast_wpseo_content_score`
- `_yoast_wpseo_readability_score`
- `_yoast_wpseo_estimated-reading-time`

### 3. Focus Keywords
- `_yoast_wpseo_focuskw` - Convert to hashtags
- `_yoast_wpseo_focuskw_text_input`

### 4. Schema Markup
- `_yoast_wpseo_schema_article_type`
- `_yoast_wpseo_schema_page_type`

### 5. News SEO
- `_yoast_wpseo_newssitemap-news_keywords`
- `_yoast_wpseo_newssitemap-news_genre`

## Conclusion

The Yoast SEO integration significantly enhances the quality of content posted to Bluesky by leveraging professionally optimized metadata. The priority system ensures the best possible content is used while maintaining backward compatibility and providing comprehensive debugging capabilities.

This integration makes the WP Bsky AutoPoster plugin a powerful tool for content creators who want to ensure their social media posts are optimized for maximum engagement and consistency with their SEO strategy. 