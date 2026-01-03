# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.0] - 2024-12-19

### Added
- Clickable cashtags for stock tickers from Yoast SEO News
  - Stock tickers from `_yoast_wpseo_newssitemap-stocktickers` are now converted to clickable cashtags on Bluesky
  - Cashtags are processed with proper facets for Bluesky's AT Protocol
  - Enhanced inline hashtag processing to handle cashtags
  - Debug logging for cashtag processing and facet creation
- Comprehensive test suite for cashtag functionality

### Changed
- Improved hashtag and cashtag processing in Bluesky API calls
- Enhanced inline tag processing to support both hashtags and cashtags

## [1.4.4] - 2024-12-19

### Fixed
- Changed default post template from `{title} - {link}` to `{title} - {excerpt}` to eliminate "text-only" URLs in Bluesky posts. The rich preview embed still provides clickable links.

## [1.4.3] - 2024-06-03

### Added
- Base URL override option for post links. If set, the plugin will replace the host part of your post links with the Base URL before adding UTM or other parameters. Useful for correcting or redirecting the site URL in your feed.

## [1.3.0] - 2024-03-20

### Added
- Support for inline hashtags in post content
- Enhanced hashtag handling and formatting
- Improved post template flexibility

## [1.2.0] - 2024-03-19

### Added
- Comprehensive logging system
  - Configurable log levels (Error, Warning, Success, Debug)
  - Custom log file location support
  - Built-in log viewer with color-coded entries
  - Log management features (view, refresh, clear)
- UTM parameter support for link tracking
- Fallback text option for posts without excerpts

### Changed
- Improved error handling and user feedback
- Enhanced settings page organization

## [1.1.0] - 2024-03-15

### Added
- Support for custom post templates
- Hashtag support from WordPress tags
- Featured image support

### Changed
- Improved error handling
- Enhanced settings page layout

## [1.0.0] - 2024-03-10

### Added
- Initial release
- Basic Bluesky posting functionality
- Settings page for configuration
- Support for post titles and excerpts
- Basic error handling 