# WP AutoPoster to Bluesky

A WordPress plugin that automatically posts new WordPress posts to Bluesky with rich link previews.

[![OpenSSF Scorecard](https://api.scorecard.dev/projects/github.com/abragad/wp-bsky-autoposter/badge)](https://scorecard.dev/viewer/?uri=github.com/abragad/wp-bsky-autoposter)

## Features

- Automatically posts new WordPress posts to Bluesky
- Supports customizable post templates with placeholders
- Includes rich link previews with post title, description, and featured image
- Automatically includes post tags as hashtags
- Secure authentication using Bluesky App Password
- Easy to use settings page
- UTM parameter tracking for analytics
- Optional Base URL override for post links (replace the host part of the post URL before adding UTM parameters)
- Comprehensive logging system with:
  - Configurable log levels (Error, Warning, Success, Debug)
  - Custom log file location
  - Built-in log viewer with color-coded entries
  - Log management (view, refresh, clear)

## Installation

1. Download the plugin files and upload them to your `/wp-content/plugins/wp-bsky-autoposter` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Bluesky AutoPoster to configure the plugin

## Configuration

1. Enter your Bluesky handle (e.g., @username.bsky.social) or DID
2. Generate an App Password in your Bluesky account settings and enter it
3. Customize the post template using the available placeholders:
   - `{title}` - Post title
   - `{excerpt}` - Post excerpt
   - `{link}` - Post URL
   - `{hashtags}` - Post tags formatted as hashtags
4. Optionally set a fallback text to use when the post excerpt is empty
5. Configure UTM parameters for link tracking:
   - Enable/disable link tracking
   - Set UTM parameters (source, medium, campaign, term, content)
   - Use {id} and {slug} placeholders in parameter values
   - Suggested defaults: source=bsky, medium=social, campaign=feed
6. (Optional) Set a Base URL to override the host part of your post links. If set, the plugin will replace the original site host with your specified Base URL before adding any UTM or other parameters. This is useful if your feed exposes an incorrect host or you want to redirect to a different site.
7. Choose minimum log level (Error Only, Warning and Above, Success and Above, Debug)
8. View current log file location
9. Set custom log file path
10. Access built-in log viewer with color-coded entries
11. Clear logs when needed

## Hashtag Support

The plugin automatically converts WordPress post tags into Bluesky hashtags. When you use the `{hashtags}` placeholder in your post template, it will be replaced with all the post's tags formatted as hashtags. For example:

- If your post has tags "market-analysis" and "investments"
- And your template includes `{hashtags}`
- The output will be: `#market-analysis #investments`

The hashtags are generated from the tag slugs, ensuring they are properly formatted for Bluesky (lowercase, with hyphens instead of spaces).

## Link Tracking

The plugin supports UTM parameter tracking for analytics. You can:
- Enable/disable link tracking
- Configure UTM parameters (source, medium, campaign, term, content)
- Use {id} and {slug} placeholders in parameter values
- Suggested default values: source=bsky, medium=social, campaign=feed

This helps you track traffic coming from Bluesky in your analytics tools.

## Base URL Override

You can optionally set a Base URL in the plugin settings. If set, the plugin will replace the host part of your post links with the Base URL before adding any UTM or other parameters. For example, if your post link is `http://originalsite.com/post-path/` and you set the Base URL to `https://example.com`, the final link will be `https://example.com/post-path/` (plus any UTM parameters if enabled). This is useful if your feed exposes an incorrect host or you want to redirect to a different site.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Active Bluesky account
- App Password from Bluesky

## Security

- All settings are stored securely in the WordPress database
- App Password is stored encrypted
- No direct access to your Bluesky account password

## Support

For support, please open an issue on the [GitHub repository](https://github.com/abragad/wp-bsky-autoposter).

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Alessio Bragadini](https://techartconsulting.it/alessio-bragadini/)

## Changelog

### 1.4.3
- Added Base URL override option for post links. If set, the plugin will replace the host part of your post links with the Base URL before adding UTM or other parameters.

### 1.4.1
- Added Italian translation

### 1.4.0
- Added WordPress.org translation support
- Added translator comments for all translatable strings
- Improved string formatting for better translation
- Fixed placeholder ordering in translated strings

### 1.3.0
- Added support for inline hashtags in post content
- Improved hashtag handling and formatting
- Enhanced post template flexibility

### 1.2.0
Improve logging

### 1.1.3
- Added proper language detection using WordPress site language
- Improved image upload validation and error handling
- Added detailed logging for image processing and uploads
- Fixed issues with invalid image types and redirects

### 1.1.2
- Improved image handling for rich previews
- Added smart image size selection with automatic fallback
- Fixed issues with large images exceeding Bluesky's size limit
- Added detailed logging for image processing

### 1.1.1
- Fixed HTML entity encoding in rich preview cards
- Improved handling of special characters in post titles and descriptions

### 1.1.0
- Added support for UTM parameter tracking for better analytics
- Added utm_source, utm_medium, and utm_campaign parameters to post URLs

### 1.0.2
- Reduced grace period for post updates from 60 to 10 seconds to better prevent duplicate posts
- Improved handling of post updates vs new posts

### 1.0.1
- Fixed HTML entity decoding for special characters in post titles and excerpts
- Improved handling of international characters

### 1.0.0
- Initial release
- Automatic posting of new WordPress posts
- Rich link previews with images
- Customizable post templates
- Hashtag support
- Connection testing
- Scheduled post support
