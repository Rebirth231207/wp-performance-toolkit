<?php
/**
 * Plugin Name:       WordPress Performance Toolkit
 * Plugin URI:        https://github.com/Rebirth231207/wp-performance-toolkit
 * Description:       A developer-first, zero-overhead performance optimization and diagnostics suite.
 * Version:           1.0.0
 * Author:            Mohammad Hadi Salimi
 * Author URI:        https://t.me/Salimi_Developer
 * Text Domain:       wp-performance-toolkit
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.8
 * License:           GPLv3
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Plugin Constants.
 */
define( 'WPPT_VERSION', '1.0.0' );
define( 'WPPT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPT_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load the Autoloader.
 */
require_once WPPT_PATH . 'includes/class-autoloader.php';

/**
 * Initialize the Plugin.
 */
function run_wp_performance_toolkit() {
	// Start Autoloader.
	new WPPT\Includes\Autoloader();

	// Load Internationalization.
	$i18n = new WPPT\Includes\I18n();
	add_action( 'plugins_loaded', [ $i18n, 'load_textdomain' ] );

	// Initialize Module Manager.
	new WPPT\Includes\Module_Manager();

	// Initialize Admin Dashboard.
	if ( is_admin() ) {
		$admin = new WPPT\Admin\Admin_Settings();
		add_action( 'admin_menu', [ $admin, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_assets' ] );
	}
}

run_wp_performance_toolkit();

/**
 * Deactivation Hook.
 * Clears scheduled cron tasks.
 */
register_deactivation_hook( __FILE__, 'wppt_deactivate' );
function wppt_deactivate() {
	// We instantiate the class manually to clear the cron if it exists.
	if ( file_exists( WPPT_PATH . 'modules/database-optimizer/class-database-optimizer.php' ) ) {
		require_once WPPT_PATH . 'includes/abstract-module.php';
		require_once WPPT_PATH . 'modules/database-optimizer/class-database-optimizer.php';
		$db_opt = new WPPT\Modules\Database_Optimizer();
		$db_opt->clear_scheduled_tasks();
	}
}