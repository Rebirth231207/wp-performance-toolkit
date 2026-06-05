<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML Minifier Module
 * 
 * Compresses the final HTML output by removing comments and collapsing whitespace 
 * while preserving pre-formatted and script blocks.
 */
class Html_Minifier extends Abstract_Module {

	protected $id = 'html-minifier';
	protected $name = 'HTML Minifier';
	protected $description = 'Minifies HTML output to reduce page weight and improve network transfer speeds.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only run on the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Attach to template_redirect to catch the start of the page output.
		add_action( 'template_redirect', [ $this, 'init_minification' ], 9999 );
	}

	/**
	 * Initializes output buffering with the minification callback.
	 */
	public function init_minification(): void {
		// Ensure we don't minify non-HTML requests.
		if ( $this->is_excluded_request() ) {
			return;
		}

		// Start output buffering with our processing function.
		ob_start( [ $this, 'minify_html' ] );
	}

	/**
	 * Logic to exclude specific request types from minification.
	 * 
	 * @return bool True if the request should be skipped.
	 */
	private function is_excluded_request(): bool {
		return (
			is_feed() || 
			is_robots() || 
			is_trackback() || 
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) || 
			( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || 
			wp_doing_ajax()
		);
	}

	/**
	 * The core minification logic using highly optimized Regex.
	 * 
	 * @param string $buffer The raw HTML content from the output buffer.
	 * @return string The minified HTML.
	 */
	public function minify_html( $buffer ): string {
		if ( empty( $buffer ) || ! is_string( $buffer ) ) {
			return (string) $buffer;
		}

		/**
		 * REGEX EXPLANATION:
		 * 
		 * 1. (?ix) -> Case-insensitive and ignore whitespace in pattern.
		 * 2. (?>[^\S\f\r\n\t]+ ...) -> Match horizontal whitespace.
		 * 3. (?=[^<]*(?:<(?!(\/)?(?:pre|code|textarea|script|style)\b)[^<]*)*? ...) -> Lookahead 
		 *    to ensure we are NOT inside protected tags (pre, code, textarea, script, style).
		 * 4. (?s)<!--(?!\[if).*?--> -> Match HTML comments except IE conditional comments.
		 */
		$regex = '/(?ix)
			(?> [^\S\f\r\n\t]+
				(?=[^<]*+
					(?:
						< (?! \/? (?:textarea|pre|code|script|style) \b ) [^<]*+
					)*+
					(?: < (?> \/? (?:textarea|pre|code|script|style) \b ) | $ )
				)
			)
			| (?s)
				<!-- (?! \[if ) .*? -->
		/x';

		$minified = preg_replace( $regex, ' ', $buffer );

		// Final check to ensure we didn't break the string; return original on error.
		return ( null !== $minified ) ? $minified : $buffer;
	}
}