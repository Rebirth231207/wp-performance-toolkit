<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI Tools Integrator Module
 * 
 * Provides command-line utilities for performance maintenance and 
 * system diagnostics via 'wp perf'.
 */
class Wp_Cli_Tools extends Abstract_Module {

	protected $id = 'wp-cli-tools';
	protected $name = 'WP-CLI Tools';
	protected $description = 'Adds performance automation commands to the WP-CLI terminal.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only register if we are in a WP-CLI environment.
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		// Register the 'wp perf' command namespace.
		try {
			\WP_CLI::add_command( 'perf', __NAMESPACE__ . '\Wp_Cli_Commands' );
		} catch ( \Exception $e ) {
			// Fail silently if CLI registration fails.
		}
	}
}

/**
 * Internal class to handle WP-CLI command logic.
 */
class Wp_Cli_Commands {

	/**
	 * Cleans up the database by removing expired transients and orphaned meta.
	 * 
	 * ## EXAMPLES
	 * 
	 *     wp perf db-clean
	 * 
	 * @when after_wp_load
	 */
	public function db_clean( $args, $assoc_args ): void {
		global $wpdb;

		\WP_CLI::line( 'Starting database optimization...' );

		// 1. Clean Expired Transients.
		$time    = time();
		$sql_ext = $wpdb->prepare(
			"DELETE o1, o2 FROM {$wpdb->options} o1 
			 INNER JOIN {$wpdb->options} o2 ON o1.option_name = REPLACE(o2.option_name, '_timeout', '') 
			 WHERE o2.option_name LIKE %s AND o2.option_value < %d",
			$wpdb->esc_like( '_transient_timeout_' ) . '%',
			$time
		);
		$cleaned_transients = $wpdb->query( $sql_ext );

		// 2. Clean Orphaned Post Meta.
		$sql_meta = "DELETE pm FROM {$wpdb->postmeta} pm 
					 LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id 
					 WHERE wp.ID IS NULL";
		$cleaned_meta = $wpdb->query( $sql_meta );

		\WP_CLI::success( sprintf( 
			'Cleanup complete. Removed %d expired transients and %d orphaned meta records.', 
			absint( $cleaned_transients ), 
			absint( $cleaned_meta ) 
		) );
	}

	/**
	 * Displays the toolkit status and server performance metrics.
	 * 
	 * ## EXAMPLES
	 * 
	 *     wp perf status
	 * 
	 * @when after_wp_load
	 */
	public function status( $args, $assoc_args ): void {
		\WP_CLI::line( '--- WP Performance Toolkit Status ---' );
		
		// Server Metrics
		\WP_CLI::line( sprintf( 'PHP Version:   %s', phpversion() ) );
		\WP_CLI::line( sprintf( 'Memory Limit:  %s', ini_get( 'memory_limit' ) ) );
		\WP_CLI::line( sprintf( 'WP_DEBUG:      %s', defined( 'WP_DEBUG' ) && WP_DEBUG ? 'ON' : 'OFF' ) );
		
		// Check for Object Cache
		$has_object_cache = wp_using_ext_object_cache() ? 'Active' : 'Inactive';
		\WP_CLI::line( sprintf( 'Object Cache:  %s', $has_object_cache ) );

		\WP_CLI::line( '------------------------------------' );
		\WP_CLI::success( 'Status check complete.' );
	}
}