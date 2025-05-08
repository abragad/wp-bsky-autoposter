# AutoPoster to Bluesky

A WordPress plugin that automatically posts new WordPress posts to Bluesky with rich link previews.

## Features

- Automatically posts new WordPress posts to Bluesky
- Supports customizable post templates with placeholders
- Includes rich link previews with post title, description, and featured image
- Secure authentication using Bluesky App Password
- Easy to use settings page

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
4. Optionally set a fallback text to use when the post excerpt is empty

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

Developed by [Alessio Bragadini](https://techartconsulting.it/app/)
