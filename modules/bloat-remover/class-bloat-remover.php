<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Bloat Remover Module
 * 
 * Removes unnecessary native WordPress scripts, styles, and meta tags 
 * to clean the DOM and improve cacheability.
 */
class Bloat_Remover extends Abstract_Module {

	protected $id = 'bloat-remover';
	protected $name = 'Core Bloat Remover';
	protected $description = 'Removes Emojis, WP-Embeds, Global Styles, and asset versioning to trim the DOM.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// 1. Remove Emojis (Admin and Frontend)
		$this->disable_emojis();

		// 2. Frontend Only Optimizations
		if ( ! is_admin() && ! is_customize_preview() ) {
			// Remove WP Generator
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );

			// Remove Embeds
			add_action( 'wp_footer', [ $this, 'disable_embeds' ] );

			// Remove Asset Versions (?ver=)
			add_filter( 'script_loader_src', [ $this, 'remove_asset_version' ], 15 );
			add_filter( 'style_loader_src', [ $this, 'remove_asset_version' ], 15 );

			// Remove Global Styles & Block Library Bloat
			add_action( 'wp_enqueue_scripts', [ $this, 'remove_global_styles' ], 100 );
		}
	}

	/**
	 * Disables the native WordPress emoji functionality.
	 */
	private function disable_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		// Remove from TinyMCE editor
		add_filter( 'tiny_mce_plugins', function( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, [ 'wpemoji' ] );
			}
			return [];
		});

		// Remove emoji preconnect hint
		add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
			if ( 'dns-prefetch' === $relation_type ) {
				$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/14.0.0/svg/' );
				$urls = array_diff( $urls, [ $emoji_svg_url ] );
			}
			return $urls;
		}, 10, 2 );
	}

	/**
	 * Dequeues the wp-embed script.
	 */
	public function disable_embeds(): void {
		wp_dequeue_script( 'wp-embed' );
		wp_deregister_script( 'wp-embed' );
	}

	/**
	 * Removes the version parameter (?ver=) from scripts and styles.
	 * 
	 * @param string $src The asset URL.
	 * @return string The modified URL.
	 */
	public function remove_asset_version( string $src ): string {
		if ( ! $src ) {
			return $src;
		}

		// Only remove if it contains ?ver=
		if ( strpos( $src, 'ver=' ) !== false ) {
			$src = remove_query_arg( 'ver', $src );
		}
		
		return $src;
	}

	/**
	 * Removes Gutenberg global styles and classic theme inline CSS.
	 */
	public function remove_global_styles(): void {
		// Dequeue the global styles SVGs and CSS
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
		
		// Optional: Remove block library if you are not using Gutenberg at all
		// wp_dequeue_style( 'wp-block-library' );
		// wp_dequeue_style( 'wp-block-library-theme' );
	}
}