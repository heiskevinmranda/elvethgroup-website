<?php 
/**
 *
 * @package   GS Envato Portfolio
 * @author    GS Plugins <hello@gsplugins.com>
 * @license   GPL-2.0+
 * @link      https://www.gsplugins.com/
 * @copyright 2017 GS Plugins
 *
 * @wordpress-plugin
 * Plugin Name:			GS Portfolio for Envato Lite
 * Plugin URI:			https://www.gsplugins.com/wordpress-plugins/
 * Description:       	A responsive Envato Portfolio WordPress plugin to showcase products from ThemeForest and CodeCanyon anywhere on your website. Easily display items using shortcodes like [gs_envato theme="gs_envato_theme1"] or widgets. View the <a href="https://envato.gsplugins.com/">Envato Portfolio Demo</a> and <a href="https://docs.gsplugins.com/gs-envato-portfolio/">Documentation</a> to get started quickly.
 * Version:           	1.4.2
 * Author:       		GS Plugins
 * Author URI:       	https://www.gsplugins.com/
 * Text Domain:       	gs-envato
 * License:           	GPL-2.0+
 * License URI:       	http://www.gnu.org/licenses/gpl-2.0.txt
*/

if( ! defined( 'GSENVATO_HACK_MSG' ) ) define( 'GSENVATO_HACK_MSG', __( 'Sorry cowboy! This is not your place', 'gs-envato' ) );

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) die( GSENVATO_HACK_MSG );

/**
 * Defining constants
 */
if( ! defined( 'GSENVATO_VERSION' ) ) define( 'GSENVATO_VERSION', '1.4.2' );
if( ! defined( 'GSENVATO_MENU_POSITION' ) ) define( 'GSENVATO_MENU_POSITION', 31 );
if( ! defined( 'GSENVATO_PLUGIN_DIR' ) ) define( 'GSENVATO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if( ! defined( 'GSENVATO_FILES_DIR' ) ) define( 'GSENVATO_FILES_DIR', GSENVATO_PLUGIN_DIR . 'gs-envato-assets' );
if( ! defined( 'GSENVATO_PLUGIN_URI' ) ) define( 'GSENVATO_PLUGIN_URI', plugins_url( '', __FILE__ ) );
if( ! defined( 'GSENVATO_FILES_URI' ) ) define( 'GSENVATO_FILES_URI', GSENVATO_PLUGIN_URI . '/gs-envato-assets' );

function disabel_envato_pro() {
	if ( is_plugin_active( 'gs-envato-portfolio-pro/gs_envato_market.php' ) ) {
		deactivate_plugins( 'gs-envato-portfolio-pro/gs_envato_market.php', true );
	}
}

register_activation_hook( __FILE__, 'disabel_envato_pro' );

/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_gs_envato_portfolio() {

    if ( ! class_exists( 'GSEnvatoAppSero\Insights' ) ) {
        require_once GSENVATO_FILES_DIR . '/appsero/Client.php';
    }

    $client = new GSEnvatoAppSero\Client( '1d25e482-f52d-41c5-b3d9-e8598c9ec923', 'GS Portfolio – Envato', __FILE__ );

    // Active insights
    $client->insights()->init();
}

appsero_init_tracker_gs_envato_portfolio();

add_action( 'plugins_loaded', function() {
    require_once GSENVATO_FILES_DIR . '/includes/gs-envato-root.php';
}, -999999 );

