<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Heartbeat API Controller Module
 * 
 * Controls the frequency of the WordPress Heartbeat API or disables it 
 * to reduce server CPU usage and prevent admin-ajax.php bloat.
 */
class Heartbeat_Controller extends Abstract_Module {

	protected $id = 'heartbeat-controller';
	protected $name = 'Heartbeat API Controller';
	protected $description = 'Manages the frequency of Heartbeat API requests to save server resources.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// 1. Completely disable Heartbeat on the frontend.
		add_action( 'wp_enqueue_scripts', [ $this, 'disable_heartbeat_on_frontend' ], 1 );

		// 2. Control Heartbeat frequency in the admin area.
		add_filter( 'heartbeat_settings', [ $this, 'optimize_admin_heartbeat' ] );
	}

	/**
	 * Deregisters the Heartbeat script on the frontend.
	 * This prevents any background AJAX calls from regular site visitors.
	 */
	public function disable_heartbeat_on_frontend(): void {
		// Only run on the frontend and ensure we don't break the admin bar for logged-in users 
		// who might need it, though usually, frontend heartbeat is unnecessary regardless.
		if ( ! is_admin() ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Throttles the Heartbeat frequency based on the current admin screen.
	 * 
	 * @param array $settings Existing Heartbeat settings.
	 * @return array Modified settings.
	 */
	public function optimize_admin_heartbeat( array $settings ): array {
		global $pagenow;

		/**
		 * Safety Check: 
		 * We must keep Heartbeat functional on post editing screens to allow 
		 * autosave and post-locking to work correctly.
		 */
		if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
			// Keep at default or a sensible minimum for editing (e.g., 60s)
			$settings['interval'] = 60; 
			return $settings;
		}

		/**
		 * For all other admin pages (Dashboard, Settings, Plugins, etc.),
		 * we heavily throttle the requests to once every 2 minutes.
		 */
		$settings['interval'] = 120;

		return $settings;
	}
}