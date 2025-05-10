=== AutoPoster to Bluesky ===
Contributors: abragad
Tags: bluesky, social media, automation, at protocol
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically posts new WordPress posts to Bluesky with rich link previews.

== Description ==

AutoPoster to Bluesky is a WordPress plugin that automatically publishes your new posts to Bluesky with rich link previews. It supports customizable post templates, hashtags, link tracking and smart text replacements.

= Features =

* Automatic posting of new WordPress posts to Bluesky
* Rich link previews with post title, description, and featured image
* Customizable post templates with placeholders
* Automatic hashtag generation from post tags
* UTM parameter tracking for analytics
* Smart text replacements for hashtags, handles, and cashtags

= Smart Replacements =

The plugin includes a Smart Replacements feature that allows you to automatically replace specific words or phrases in your posts with hashtags, handles, or cashtags. For example:

* Replace "bitcoin" with "#bitcoin"
* Replace "Apple" with "$AAPL"
* Replace "Washington Post" with "@washingtonpost.com"

The replacements are applied to both the post title and excerpt, and they only match whole words to prevent unwanted replacements. The feature also includes safety checks to prevent breaking URLs or markdown formatting.

= Link Tracking =

The plugin supports UTM parameter tracking for analytics:

* Enable/disable link tracking
* Configure UTM parameters (source, medium, campaign, term, content)
* Use {id} and {slug} placeholders in parameter values
* Suggested default values: source=bsky, medium=social, campaign=feed

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

Yes, you can customize the post template using placeholders for title, excerpt, link, and hashtags. You can also use the Smart Replacements feature to automatically convert specific words to hashtags, handles, or cashtags.

= How does link tracking work? =

The plugin can add UTM parameters to your post links when they're shared on Bluesky. This helps you track traffic coming from Bluesky in your analytics. You can configure the UTM parameters in the plugin settings, and use {id} and {slug} placeholders to include post-specific information.

= How do Smart Replacements work? =

Smart Replacements allow you to automatically replace specific words or phrases in your posts with hashtags, handles, or cashtags. For example, you can set up a rule to replace "bitcoin" with "#bitcoin". The replacements are applied to both the post title and excerpt, and they only match whole words to prevent unwanted replacements.

== Screenshots ==

1. Plugin settings page
2. Connection test functionality
3. Post template configuration
4. Example of a post on Bluesky
5. Link tracking settings
6. Smart Replacements configuration

== Changelog ==

= 1.2.0 =
* Added Smart Replacements feature for automatic text substitutions
* Added support for hashtags, handles, and cashtags in replacements
* Added safety checks to prevent breaking URLs and markdown
* Improved settings organization

= 1.1.0 =
* Added UTM parameter tracking for analytics
* Added support for {id} and {slug} placeholders in UTM parameters
* Added link tracking settings section
* Improved settings organization

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

= 1.2.0 =
Added Smart Replacements feature for automatic text substitutions with support for hashtags, handles, and cashtags.

= 1.1.0 =
Added UTM parameter tracking for analytics with support for {id} and {slug} placeholders.

= 1.0.1 =
Fixed HTML entity decoding for special characters in post titles and excerpts.

= 1.0.0 =
Initial release of AutoPoster to Bluesky.

== Privacy Policy ==

This plugin does not collect any personal data. It only uses the Bluesky credentials you provide to post your content to Bluesky. 