<?php
namespace WPPT\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings & Dashboard Manager
 * 
 * Handles the registration of the admin menu, asset enqueuing, 
 * and the rendering of the telemetry-driven dashboard.
 */
class Admin_Settings {

	/**
	 * Register the top-level "Performance" menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Performance Toolkit', 'wp-performance-toolkit' ),
			__( 'Performance', 'wp-performance-toolkit' ),
			'manage_options',
			'wp-performance-toolkit',
			[ $this, 'render_dashboard' ],
			'dashicons-performance',
			2
		);
	}

	/**
	 * Enqueue assets only on our dashboard and pass dynamic telemetry data.
	 * 
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_wp-performance-toolkit' !== $hook ) {
			return;
		}

		// Enqueue the pure CSS and Vanilla JS built in previous phases.
		wp_enqueue_style( 'wppt-admin-css', WPPT_URL . 'admin/assets/css/admin.css', [], WPPT_VERSION );
		wp_enqueue_script( 'wppt-admin-js', WPPT_URL . 'admin/assets/js/admin.js', [], WPPT_VERSION, true );

		// Gather telemetry and pass it to the JS dashboard.
		wp_localize_script( 'wppt-admin-js', 'wpptStats', $this->get_dashboard_telemetry() );
	}

	/**
	 * Collects real-time performance and system data.
	 * 
	 * @return array The telemetry data package.
	 */
	private function get_dashboard_telemetry(): array {
		global $wpdb;

		// 1. Fetch the latest snapshot from Module 1 (Performance Analyzer).
		$snapshot = get_transient( 'wppt_latest_performance_snapshot' );
		$history  = get_option( 'wppt_performance_history', [] );

		// 2. Calculate DB Overhead (Data Free).
		$db_status   = $wpdb->get_results( "SHOW TABLE STATUS", ARRAY_A );
		$db_overhead = 0;
		if ( $db_status ) {
			foreach ( $db_status as $table ) {
				$db_overhead += (int) $table['Data_free'];
			}
		}
		$db_overhead_mb = round( $db_overhead / 1024 / 1024, 2 );

		// 3. Count Active Modules (Directory Scan).
		$module_count = 0;
		$modules_path = WPPT_PATH . 'modules/';
		if ( is_dir( $modules_path ) ) {
			$it = new \RecursiveDirectoryIterator( $modules_path );
			foreach ( new \RecursiveIteratorIterator( $it ) as $file ) {
				if ( $file->getExtension() === 'php' && strpos( $file->getFilename(), 'class-' ) === 0 ) {
					$module_count++;
				}
			}
		}

		// 4. Calculate Dynamic Performance Score (0-100).
		$ttfb      = $snapshot['ttfb'] ?? 0;
		$load_time = $snapshot['load_time'] ?? 0;
		
		$ttfb_score = ( $ttfb > 0 ) ? max( 0, min( 100, 100 - ( ( $ttfb - 200 ) / 8 ) ) ) : 0;
		$load_score = ( $load_time > 0 ) ? max( 0, min( 100, 100 - ( ( $load_time - 1500 ) / 35 ) ) ) : 0;
		
		$final_score = ( $ttfb > 0 && $load_time > 0 ) ? round( ( $ttfb_score + $load_score ) / 2 ) : 0;

		return [
			'score'       => $final_score,
			'ttfb'        => $ttfb,
			'load_time'   => $load_time,
			'requests'    => $snapshot['requests'] ?? 0,
			'dom_count'   => $snapshot['dom_count'] ?? 0,
			'db_overhead' => $db_overhead_mb,
			'modules'     => $module_count,
			'history'     => $history,
			'nonce'       => wp_create_nonce( 'wppt_admin_nonce' ),
			'i18n'        => [
				'no_data' => __( 'Waiting for telemetry... Visit your frontend to trigger analysis.', 'wp-performance-toolkit' )
			]
		];
	}

	/**
	 * Renders the dashboard HTML skeleton.
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap wppt-dashboard">
			<header class="wppt-header">
				<h1><?php echo esc_html__( 'WP Performance Toolkit', 'wp-performance-toolkit' ); ?></h1>
				<p class="description"><?php echo esc_html__( 'High-performance monitoring and surgical optimization hub.', 'wp-performance-toolkit' ); ?></p>
			</header>

			<!-- Performance Score Section -->
			<section class="wppt-score-card">
				<div class="wppt-score-circle">
					<!-- SVG Gauge generated via JS -->
					<span class="wppt-score-value">--</span>
				</div>
				<div class="wppt-score-meta">
					<div class="wppt-status-badge">
						<span class="status pulse"></span>
						<?php esc_html_e( 'System Status: Active', 'wp-performance-toolkit' ); ?>
					</div>
					<p id="wppt-last-run-msg" style="margin-top: 10px; color: var(--wppt-text-muted); font-size: 13px;">
						<?php esc_html_e( 'Telemetry is collected in real-time from your live site visitors.', 'wp-performance-toolkit' ); ?>
					</p>
				</div>
			</section>

			<!-- Metrics Grid -->
			<div class="wppt-grid">
				<article class="wppt-card">
					<h3><?php esc_html_e( 'Server Response (TTFB)', 'wp-performance-toolkit' ); ?></h3>
					<div class="wppt-metric" id="metric-ttfb">--ms</div>
				</article>

				<article class="wppt-card">
					<h3><?php esc_html_e( 'Full Page Load', 'wp-performance-toolkit' ); ?></h3>
					<div class="wppt-metric" id="metric-load-time">--ms</div>
				</article>

				<article class="wppt-card">
					<h3><?php esc_html_e( 'HTTP Requests', 'wp-performance-toolkit' ); ?></h3>
					<div class="wppt-metric" id="metric-requests">--</div>
				</article>

				<article class="wppt-card">
					<h3><?php esc_html_e( 'Active Optimizers', 'wp-performance-toolkit' ); ?></h3>
					<div class="wppt-metric" id="metric-modules">--</div>
				</article>
			</div>

			<!-- Main Layout -->
			<main class="wppt-main-content">
				<div class="wppt-chart-container">
					<div class="wppt-chart-header">
						<h2><?php esc_html_e( 'Load Time History (ms)', 'wp-performance-toolkit' ); ?></h2>
					</div>
					<div class="wppt-chart-placeholder" id="wppt-history-chart">
						<!-- SVG Sparkline generated via JS -->
					</div>
				</div>

				<aside class="wppt-sidebar">
					<div class="wppt-card">
						<h3><?php esc_html_e( 'Database Health', 'wp-performance-toolkit' ); ?></h3>
						<div class="wppt-overhead-info" style="margin-bottom: 20px;">
							<span style="font-size: 24px; font-weight: 700; color: var(--wppt-accent);" id="metric-db-overhead">0.00</span>
							<span style="font-size: 14px; color: var(--wppt-text-muted);"> MB <?php esc_html_e( 'reclaimable overhead', 'wp-performance-toolkit' ); ?></span>
						</div>
						<button id="wppt-optimize-db" class="button button-primary" style="width: 100%; height: 40px; justify-content: center; display: flex; align-items: center; border-radius: 6px;">
							<?php esc_html_e( 'Optimize Database', 'wp-performance-toolkit' ); ?>
						</butto