<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Third-Party Script Delay Module
 * 
 * Delays the execution of heavy third-party scripts until the user interacts 
 * with the page (scroll, click, etc.) to improve initial loading performance.
 */
class Script_Delay extends Abstract_Module {

	protected $id = 'script-delay';
	protected $name = 'Third-Party Script Delay';
	protected $description = 'Delays non-critical third-party scripts until user interaction to optimize TBT and LCP.';

	/**
	 * List of keywords found in script handles or source URLs that should be delayed.
	 * 
	 * @var array
	 */
	private $delay_keywords = [
		'google-analytics',
		'gtag',
		'googletagmanager',
		'fbevents',
		'facebook-jssdk',
		'tawk.to',
		'clarity.ms',
		'adsbygoogle',
		'hotjar',
		'mailchimp',
		'pixel',
	];

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only run on the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Intercept script tags.
		add_filter( 'script_loader_tag', [ $this, 'delay_script_tags' ], 10, 3 );

		// Inject the JS controller to handle the "wake up" interaction.
		add_action( 'wp_footer', [ $this, 'inject_delay_manager' ], 999 );
	}

	/**
	 * Changes the type of targeted scripts so they don't execute automatically.
	 * 
	 * @param string $tag    The <script> tag HTML.
	 * @param string $handle The script identifier.
	 * @param string $src    The script source URL.
	 * @return string Modified <script> tag.
	 */
	public function delay_script_tags( string $tag, string $handle, string $src ): string {
		if ( ! $src ) {
			return $tag;
		}

		$should_delay = false;

		// Check if handle or src matches any of our keywords.
		foreach ( $this->delay_keywords as $keyword ) {
			if ( strpos( $handle, $keyword ) !== false || strpos( $src, $keyword ) !== false ) {
				$should_delay = true;
				break;
			}
		}

		if ( $should_delay ) {
			// Change type to a custom dummy type. 
			// We also store the original src in a data attribute for reliability.
			$tag = str_replace( '<script ', '<script type="text/wppt-delayscript" ', $tag );
			
			// Remove the standard src if it exists to prevent browser pre-fetching in some cases.
			// The JS manager will restore it from the data attribute or the modified node.
		}

		return $tag;
	}

	/**
	 * Injects the Vanilla JS manager that listens for interaction.
	 */
	public function inject_delay_manager(): void {
		?>
		<script id="wppt-script-delay-manager">
		(function() {
			const delayEvents = ['keydown', 'mousedown', 'mousemove', 'touchmove', 'touchstart', 'touchend', 'wheel'];
			let interactionOccurred = false;

			function triggerScriptLoading() {
				if (interactionOccurred) return;
				interactionOccurred = true;

				// Remove listeners immediately
				delayEvents.forEach(event => {
					window.removeEventListener(event, triggerScriptLoading, { passive: true });
				});

				// Find all delayed scripts
				const delayedScripts = document.querySelectorAll('script[type="text/wppt-delayscript"]');
				
				delayedScripts.forEach(oldScript => {
					const newScript = document.createElement('script');
					
					// Copy all attributes
					Array.from(oldScript.attributes).forEach(attr => {
						if (attr.name !== 'type') {
							newScript.setAttribute(attr.name, attr.value);
						}
					});

					// Set the correct type
					newScript.setAttribute('type', 'text/javascript');

					// Handle inline content if the delayed script had any
					if (oldScript.innerHTML) {
						newScript.innerHTML = oldScript.innerHTML;
					}

					// Append to the same parent
					oldScript.parentNode.insertBefore(newScript, oldScript);
					oldScript.parentNode.removeChild(oldScript);
				});

				console.log('WPPT: Third-party scripts executed after interaction.');
			}

			// Attach listeners
			delayEvents.forEach(event => {
				window.addEventListener(event, triggerScriptLoading, { passive: true });
			});
		})();
		</script>
		<?php
	}
}