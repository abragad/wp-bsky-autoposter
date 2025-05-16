# WP AutoPoster to Bluesky

A WordPress plugin that automatically posts new WordPress posts to Bluesky with rich link previews.

## Features

- Automatically posts new WordPress posts to Bluesky
- Supports customizable post templates with placeholders
- Includes rich link previews with post title, description, and featured image
- Automatically includes post tags as hashtags
- Secure authentication using Bluesky App Password
- Easy to use settings page
- UTM parameter tracking for analytics

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
