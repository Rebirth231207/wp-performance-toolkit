<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performance Analyzer Module
 * 
 * Collects real-world performance metrics from the user's browser.
 */
class Performance_Analyzer extends Abstract_Module {

	protected $id = 'performance-analyzer';
	protected $name = 'Performance Analyzer';

	public function run(): void {
		// Only run on the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		add_action( 'wp_footer', [ $this, 'inject_telemetry_script' ], 999 );
		
		// AJAX handlers for telemetry (one for logged-in users, one for guests).
		add_action( 'wp_ajax_wppt_save_telemetry', [ $this, 'save_telemetry_data' ] );
		add_action( 'wp_ajax_nopriv_wppt_save_telemetry', [ $this, 'save_telemetry_data' ] );
	}

	/**
	 * Injects highly-optimized Vanilla JS for metric collection.
	 */
	public function inject_telemetry_script(): void {
		$nonce = wp_create_nonce( 'wppt_telemetry_nonce' );
		$ajax_url = admin_url( 'admin-ajax.php' );
		?>
		<script id="wppt-analyzer-js">
		(function() {
			window.addEventListener('load', () => {
				// Use requestIdleCallback to ensure no impact on interactivity
				const collect = window.requestIdleCallback || ((cb) => setTimeout(cb, 1000));
				
				collect(() => {
					if (!window.performance || !window.performance.getEntriesByType) return;

					const nav = performance.getEntriesByType('navigation')[0];
					const resources = performance.getEntriesByType('resource');
					
					if (!nav) return;

					// Calculate Asset Sizes (in bytes)
					let metrics = {
						js: 0,
						css: 0,
						img: 0,
						total_requests: resources.length + 1
					};

					resources.forEach(res => {
						const size = res.transferSize || res.encodedBodySize || 0;
						if (res.initiatorType === 'script') metrics.js += size;
						if (res.initiatorType === 'link' || res.initiatorType === 'css') metrics.css += size;
						if (res.initiatorType === 'img' || res.initiatorType === 'image') metrics.img += size;
					});

					const data = {
						action: 'wppt_save_telemetry',
						nonce: '<?php echo esc_js( $nonce ); ?>',
						url: window.location.href,
						ttfb: Math.round(nav.responseStart - nav.startTime),
						load_time: Math.round(nav.loadEventEnd - nav.startTime),
						dom_count: document.getElementsByTagName('*').length,
						requests: metrics.total_requests,
						js_size: Math.round(metrics.js / 1024), // KB
						css_size: Math.round(metrics.css / 1024), // KB
						img_size: Math.round(metrics.img / 1024), // KB
						page_size: Math.round(nav.transferSize / 1024) || 0
					};

					// Send data via Fetch API
					fetch('<?php echo esc_url( $ajax_url ); ?>', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams(data)
					});
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Validates and saves the incoming telemetry.
	 */
	public function save_telemetry_data(): void {
		// 1. Security Check
		check_ajax_referer( 'wppt_telemetry_nonce', 'nonce' );

		// 2. Data Sanitization
		$raw_data = $_POST;
		$clean_stats = [
			'ttfb'       => absint( $raw_data['ttfb'] ?? 0 ),
			'load_time'  => absint( $raw_data['load_time'] ?? 0 ),
			'dom_count'  => absint( $raw_data['dom_count'] ?? 0 ),
			'requests'   => absint( $raw_data['requests'] ?? 0 ),
			'js_kb'      => absint( $raw_data['js_size'] ?? 0 ),
			'css_kb'     => absint( $raw_data['css_size'] ?? 0 ),
			'img_kb'     => absint( $raw_data['img_size'] ?? 0 ),
			'timestamp'  => time(),
		];

		// 3. Storage
		// We store the "Latest Snapshot" for the dashboard to display.
		// Using a transient keeps the database fast and auto-cleans.
		set_transient( 'wppt_latest_performance_snapshot', $clean_stats, HOUR_IN_SECONDS );

		// Also log for history (Optional - logic for the SVG chart)
		$history = get_option( 'wppt_performance_history', [] );
		$history[] = [ 't' => time(), 'v' => $clean_stats['load_time'] ];
		update_option( 'wppt_performance_history', array_slice( $history, -10 ) ); // Keep last 10 scans

		wp_send_json_success();
	}
}