<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instant Page Preloader Module
 * 
 * Prefetches internal links on hover or touchstart to provide 
 * near-instant page transitions.
 */
class Instant_Preloader extends Abstract_Module {

	protected $id = 'instant-preloader';
	protected $name = 'Instant Page Preloader';
	protected $description = 'Predictively prefetches internal pages when a user hovers over a link, making navigation feel instantaneous.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only run on the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Inject the preloader script in the footer.
		add_action( 'wp_footer', [ $this, 'inject_preloader_script' ], 999 );
	}

	/**
	 * Injects the Vanilla JS preloader logic.
	 */
	public function inject_preloader_script(): void {
		?>
		<script id="wppt-instant-preloader">
		(function() {
			const prefetched = new Set();
			const hoverDelay = 65;
			let hoverTimer = null;

			/**
			 * Core prefetch function.
			 */
			function prefetch(url) {
				if (prefetched.has(url)) return;

				const link = document.createElement('link');
				link.rel = 'prefetch';
				link.href = url;
				document.head.appendChild(link);
				
				prefetched.add(url);
			}

			/**
			 * Validates if a URL should be prefetched.
			 */
			function isEligible(anchor) {
				if (!anchor || !anchor.href) return false;

				const url = anchor.href;
				const origin = window.location.origin;

				// 1. Must be internal domain.
				if (!url.startsWith(origin) && !url.startsWith('/')) return false;

				// 2. Exclude specific patterns (Admin, Login, Actions, Anchors).
				const excludes = [
					'/wp-admin/',
					'/wp-login',
					'action=logout',
					'add-to-cart=',
					'wp-json',
					'#',
					'.pdf',
					'.zip',
					'.jpg',
					'.png'
				];

				for (const path of excludes) {
					if (url.includes(path)) return false;
				}

				return true;
			}

			// Listen for hover (Mouse)
			document.addEventListener('mouseover', (e) => {
				const anchor = e.target.closest('a');
				if (!isEligible(anchor)) return;

				hoverTimer = setTimeout(() => {
					prefetch(anchor.href);
				}, hoverDelay);
			}, { passive: true });

			// Clear timer if mouse leaves before delay
			document.addEventListener('mouseout', (e) => {
				if (hoverTimer) {
					clearTimeout(hoverTimer);
					hoverTimer = null;
				}
			}, { passive: true });

			// Listen for Touch (Mobile)
			document.addEventListener('touchstart', (e) => {
				const anchor = e.target.closest('a');
				if (isEligible(anchor)) {
					prefetch(anchor.href);
				}
			}, { passive: true });

		})();
		</script>
		<?php
	}
}