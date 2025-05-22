=== WP AutoPoster to Bluesky ===
Contributors: abragad
Tags: bluesky, social media, automation, at protocol
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.3.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically posts new WordPress posts to Bluesky with rich link previews.

== Description ==

WP AutoPoster to Bluesky is a WordPress plugin that automatically shares your new blog posts to Bluesky, the decentralized social network. It creates rich link previews with your post's title, description, and featured image, making your posts stand out in the Bluesky feed.

= Features =

* Automatically posts new WordPress posts to Bluesky
* Supports customizable post templates with placeholders
* Includes rich link previews with post title, description, and featured image
* Automatically includes post tags as hashtags
* Secure authentication using Bluesky App Password
* Easy to use settings page
* Connection testing functionality
* Support for scheduled posts
* UTM parameter tracking for analytics

= Post Template =

Customize how your posts appear on Bluesky using these placeholders:
* `{title}` - Post title
* `{excerpt}` - Post excerpt
* `{link}` - Post URL
* `{hashtags}` - Post tags formatted as hashtags

= Hashtag Support =

The plugin automatically converts WordPress post tags into Bluesky hashtags. When you use the `{hashtags}` placeholder in your post template, it will be replaced with all the post's tags formatted as hashtags. For example:

* If your post has tags "market-analysis" and "investments"
* And your template includes `{hashtags}`
* The output will be: `#market-analysis #investments`

The hashtags are generated from the tag slugs, ensuring they are properly formatted for Bluesky (lowercase, with hyphens instead of spaces).

= Link Tracking =

The plugin supports UTM parameter tracking for analytics. You can:
* Enable/disable link tracking
* Configure UTM parameters (source, medium, campaign, term, content)
* Use {id} and {slug} placeholders in parameter values
* Suggested default values: source=bsky, medium=social, campaign=feed

= Logging =

The plugin includes a comprehensive logging system:
* Choose minimum log level (Error Only, Warning and Above, Success and Above, Debug)
* View current log file location
* Set custom log file path
* Access built-in log viewer with color-coded entries
* Clear logs when needed

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-bsky-autoposter` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->Bluesky AutoPoster screen to configure the plugin.

== Frequently Asked Questions ==

= Do I need a Bluesky account? =

Yes, you need a Bluesky account to use this plugin. You can sign up at [bsky.app](https://bsky.app).

= How do I get an App Password? =

1. Log in to your Bluesky account
2. Go to Settings
3. Navigate to App Passwords
4. Generate a new App Password
5. Copy and paste it into the plugin settings

= Will the plugin post updates to my existing posts? =

No, the plugin only posts new content. Updates to existing posts are ignored to prevent duplicate posts.

= Can I customize how my posts appear on Bluesky? =

Yes, you can customize the post template using placeholders for title, excerpt, link, and hashtags.

= How does link tracking work? =

The plugin can add UTM parameters to your post links when they're shared on Bluesky. This helps you track traffic coming from Bluesky in your analytics. You can configure the UTM parameters in the plugin settings, and use {id} and {slug} placeholders to include post-specific information.

= How can I view the plugin logs? =

The plugin includes a built-in log viewer that you can access from the settings page. You can:
* View logs with color-coded entries
* Refresh the log view
* Clear logs when needed
* Set a custom log file location
* Choose the minimum log level to record

== Screenshots ==

1. Plugin settings page
2. Connection test functionality
3. Post template configuration
4. Example of a post on Bluesky
5. Link tracking settings
6. Log viewer

== Changelog ==

= 1.3.0 =
* Added support for inline hashtags in post content
* Improved hashtag handling and formatting
* Enhanced post template flexibility

= 1.2.0 =
* Improved logging
* Added comprehensive logging system
  * Configurable log levels (Error, Warning, Success, Debug)
  * Custom log file location support
  * Built-in log viewer with color-coded entries
  * Log management features (view, refresh, clear)
* Added UTM parameter support for link tracking
* Added fallback text option for posts without excerpts
* Improved error handling and user feedback

= 1.1.3 =
* Added proper language detection using WordPress site language
* Improved image upload validation and error handling
* Added detailed logging for image processing and uploads
* Fixed issues with invalid image types and redirects

= 1.1.2 =
* Improved image handling for rich previews
* Added smart image size selection with automatic fallback
* Fixed issues with large images exceeding Bluesky's size limit
* Added detailed logging for image processing

= 1.1.1 =
* Fixed HTML entity encoding in rich preview cards
* Improved handling of special characters in post titles and descriptions

= 1.1.0 =
* Added support for UTM parameter tracking for better analytics
* Added utm_source, utm_medium, and utm_campaign parameters to post URLs

= 1.0.2 =
* Reduced grace period for post updates from 60 to 10 seconds to better prevent duplicate posts
* Improved handling of post updates vs new posts

= 1.0.1 =
* Fixed HTML entity decoding for special characters in post titles and excerpts
* Improved handling of international characters

= 1.0.0 =
* Initial release
* Automatic posting of new WordPress posts
* Rich link previews with images
* Customizable post templates
* Hashtag support
* Connection testing
* Scheduled post support

== Upgrade Notice ==

= 1.3.0 =
Added support for inline hashtags in post content, allowing for more flexible hashtag placement and formatting.

= 1.2.0 =
Improved logging

= 1.1.3 =
Added proper language detection and improved image handling with better validation and error reporting.

= 1.1.2 =
Improved image handling for rich previews with smart size selection and automatic fallback for large images.

= 1.1.1 =
Fixed HTML entity encoding in rich preview cards for better display of special characters.

= 1.1.0 =
Added UTM parameter tracking for better analytics.

= 1.0.2 =
Improved handling of post updates to better prevent duplicate posts.

= 1.0.1 =
Fixed HTML entity decoding for special characters in post titles and excerpts.

= 1.0.0 =
Initial release of WP AutoPoster to Bluesky.

== Privacy Policy ==

This plugin does not collect any personal data. It only uses the Bluesky credentials you provide to post your content to Bluesky. 