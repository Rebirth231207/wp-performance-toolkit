<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Font & Icon Optimizer Module
 * 
 * Optimizes the loading of web fonts and prevents unnecessary icon libraries 
 * from loading on the frontend.
 */
class Font_Optimizer extends Abstract_Module {

	protected $id = 'font-optimizer';
	protected $name = 'Font & Icon Optimizer';
	protected $description = 'Optimizes Google Fonts and dequeues dashicons for improved render speed.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only optimize the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Remove Dashicons for logged-out users.
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_dashicons' ], 100 );

		// Optimize Google Font URLs.
		add_filter( 'style_loader_tag', [ $this, 'optimize_google_font_tag' ], 10, 3 );

		// Inject preconnect hints.
		add_action( 'wp_head', [ $this, 'inject_resource_hints' ], 2 );
	}

	/**
	 * Dequeue Dashicons on the frontend if the user is not logged in.
	 */
	public function dequeue_dashicons(): void {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}

	/**
	 * Scans style tags for Google Fonts and appends display=swap if missing.
	 * 
	 * @param string $tag    The style tag HTML.
	 * @param string $handle The style identifier.
	 * @param string $src    The style source URL.
	 * @return string Modified style tag.
	 */
	public function optimize_google_font_tag( string $tag, string $handle, string $src ): string {
		if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
			// Check if display=swap is already there.
			if ( strpos( $src, 'display=swap' ) === false ) {
				$new_src = add_query_arg( 'display', 'swap', $src );
				$tag     = str_replace( $src, $new_src, $tag );
			}
		}
		return $tag;
	}

	/**
	 * Injects preconnect hints for Google Fonts servers.
	 */
	public function inject_resource_hints(): void {
		global $wp_styles;

		$using_google_fonts = false;

		// Check registered styles for Google Fonts URLs.
		foreach ( $wp_styles->enqueued as $handle ) {
			if ( isset( $wp_styles->registered[ $handle ] ) && strpos( $wp_styles->registered[ $handle ]->src, 'fonts.googleapis.com' ) !== false ) {
				$using_google_fonts = true;
				break;
			}
		}

		if ( $using_google_fonts ) {
			echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
			echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		}
	}
}