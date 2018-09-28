<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/AdamEDGECreative/WP_Menu_Query
 * @since             1.0.0
 * @package           WP_Menu_Query
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Menu Query
 * Description:       Allows menus and menu items to be queried like posts.
 * Version:           1.0.0
 * Author:            Adam Taylor
 * Author URI:        https://www.iamadamtaylor.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-menu-query
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-menu-query-activator.php
 */
function activate_wp_menu_query() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-menu-query-activator.php';
	WP_Menu_Query_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-menu-query-deactivator.php
 */
function deactivate_wp_menu_query() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-menu-query-deactivator.php';
	WP_Menu_Query_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_menu_query' );
register_deactivation_hook( __FILE__, 'deactivate_wp_menu_query' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-menu-query.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_menu_query() {

	$plugin = new _WP_Menu_Query();
	$plugin->run();

}
run_wp_menu_query();
