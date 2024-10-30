<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.livewords.com/
 * @since             1.0.0
 * @package           LiveWords Translation
 *
 * @wordpress-plugin
 * Plugin Name:       LiveWords Translation
 * Plugin URI:        http://www.livewords.com/
 * Description:       The official LiveWords translation plugin.
 * Version:           1.0.3
 * Author:            LiveWords
 * Author URI:        http://www.livewords.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       livewords Translation
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-livewords-activator.php
 */
function activate_livewords() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-livewords-activator.php';
	Livewords_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-livewords-deactivator.php
 */
function deactivate_livewords() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-livewords-deactivator.php';
	Livewords_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_livewords' );
register_deactivation_hook( __FILE__, 'deactivate_livewords' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-livewords.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_livewords() {

	$plugin = new Livewords();
	$plugin->run();

}
run_livewords();
