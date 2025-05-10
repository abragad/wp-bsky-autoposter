# AutoPoster to Bluesky

A WordPress plugin that automatically posts new WordPress posts to Bluesky with rich link previews.

## Features

- Automatically posts new WordPress posts to Bluesky
- Supports customizable post templates with placeholders
- Includes rich link previews with post title, description, and featured image
- Automatically includes post tags as hashtags
- Secure authentication using Bluesky App Password
- Easy to use settings page
- UTM parameter tracking for analytics
- Smart text replacements for hashtags, handles, and cashtags

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
6. Set up Smart Replacements:
   - Add rules to automatically replace words with hashtags, handles, or cashtags
   - Example: Replace "bitcoin" with "#bitcoin"
   - Replacements only match whole words to prevent unwanted changes
   - Safety checks prevent breaking URLs or markdown formatting

## Hashtag Support

The plugin automatically converts WordPress post tags into Bluesky hashtags. When you use the `{hashtags}` placeholder in your post template, it will be replaced with all the post's tags formatted as hashtags. For example:

- If your post has tags "market-analysis" and "investments"
- And your template includes `{hashtags}`
- The output will be: `#market-analysis #investments`

The hashtags are generated from the tag slugs, ensuring they are properly formatted for Bluesky (lowercase, with hyphens instead of spaces).

## Smart Replacements

The Smart Replacements feature allows you to automatically replace specific words or phrases in your posts with hashtags, handles, or cashtags. For example:

- Replace "bitcoin" with "#bitcoin"
- Replace "Apple" with "$AAPL"
- Replace "Washington Post" with "@washingtonpost.com"

The replacements are applied to both the post title and excerpt, and they only match whole words to prevent unwanted replacements. The feature includes safety checks to prevent breaking URLs or markdown formatting.

## Link Tracking

The plugin supports UTM parameter tracking for analytics. You can:
- Enable/disable link tracking
- Configure UTM parameters (source, medium, campaign, term, content)
- Use {id} and {slug} placeholders in parameter values
- Suggested default values: source=bsky, medium=social, campaign=feed

This helps you track traffic coming from Bluesky in your analytics tools.

## Changelog

### 1.2.0
- Added Smart Replacements feature for automatic text substitutions
- Added support for hashtags, handles, and cashtags in replacements
- Added safety checks to prevent breaking URLs and markdown
- Improved settings organization

### 1.1.0
- Added UTM parameter tracking for analytics
- Added support for {id} and {slug} placeholders in UTM parameters
- Added link tracking settings section
- Improved settings organization

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
