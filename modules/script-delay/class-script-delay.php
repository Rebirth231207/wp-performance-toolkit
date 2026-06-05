<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Script_Delay extends Abstract_Module {

	protected $id = 'script-delay';
	protected $name = 'Third-Party Script Delay';

	private $delay_keywords = [ 'google-analytics', 'gtag', 'googletagmanager', 'fbevents', 'facebook-jssdk', 'tawk.to', 'clarity.ms' ];

	public function run(): void {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}
		// Priority 20: Run AFTER Script Optimizer.
		add_filter( 'script_loader_tag', [ $this, 'delay_script_tags' ], 20, 3 );
		add_action( 'wp_footer', [ $this, 'inject_delay_manager' ], 999 );
	}

	public function delay_script_tags( string $tag, string $handle, string $src ): string {
		if ( ! $src ) return $tag;

		foreach ( $this->delay_keywords as $keyword ) {
			if ( strpos( $handle, $keyword ) !== false || strpos( $src, $keyword ) !== false ) {
				// We wrap the modified tag from Script Optimizer (with defer/async) into our delay type.
				return str_replace( '<script ', '<script type="text/wppt-delayscript" ', $tag );
			}
		}

		return $tag;
	}

	public function inject_delay_manager(): void {
		?>
		<script id="wppt-script-delay-manager">
		(function() {
			const delayEvents = ['keydown', 'mousedown', 'mousemove', 'touchmove', 'touchstart', 'touchend', 'wheel'];
			let interactionOccurred = false;
			function triggerScriptLoading() {
				if (interactionOccurred) return;
				interactionOccurred = true;
				delayEvents.forEach(e => window.removeEventListener(e, triggerScriptLoading, { passive: true }));
				document.querySelectorAll('script[type="text/wppt-delayscript"]').forEach(oldScript => {
					const newScript = document.createElement('script');
					Array.from(oldScript.attributes).forEach(attr => {
						if (attr.name !== 'type') newScript.setAttribute(attr.name, attr.value);
					});
					newScript.setAttribute('type', 'text/javascript');
					if (oldScript.innerHTML) newScript.innerHTML = oldScript.innerHTML;
					oldScript.parentNode.insertBefore(newScript, oldScript);
					oldScript.parentNode.removeChild(oldScript);
				});
			}
			delayEvents.forEach(e => window.addEventListener(e, triggerScriptLoading, { passive: true }));
		})();
		</script>
		<?php
	}
}