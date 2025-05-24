<?php
/**
 * Plugin Name: WP AutoPoster to Bluesky
 * Plugin URI: https://github.com/abragad/wp-bsky-autoposter
 * Description: Automatically posts new WordPress posts to Bluesky with rich link previews.
 * Version: 1.4.0
 * Author: Alessio Bragadini <alessio@techartconsulting.it>
 * Author URI: https://techartconsulting.it/alessio-bragadini
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-bsky-autoposter
 * Domain Path: /languages
 *
 * @package WP_BSky_AutoPoster
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('WP_BSKY_AUTOPOSTER_VERSION', '1.4.0');
define('WP_BSKY_AUTOPOSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_BSKY_AUTOPOSTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_BSKY_AUTOPOSTER_PLUGIN_DIR . 'includes/class-wp-bsky-autoposter.php';
require_once WP_BSKY_AUTOPOSTER_PLUGIN_DIR . 'includes/class-wp-bsky-autoposter-settings.php';
require_once WP_BSKY_AUTOPOSTER_PLUGIN_DIR . 'includes/class-wp-bsky-autoposter-api.php';

/**
 * Load plugin text domain.
 */
function wpbskyautoposter_load_textdomain() {
    load_plugin_textdomain('wp-bsky-autoposter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'wpbskyautoposter_load_textdomain');

/**
 * Begins execution of the plugin.
 */
function run_wp_bsky_autoposter() {
    $plugin = new WP_BSky_AutoPoster();
    $plugin->run();
}

// Run the plugin
run_wp_bsky_autoposter(); 