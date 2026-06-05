<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lazy Load Controller Module
 * 
 * Enhances native WordPress lazy loading by protecting the LCP image
 * and extending support to iframes and videos.
 */
class Lazy_Load extends Abstract_Module {

	protected $id = 'lazy-load';
	protected $name = 'Lazy Load Controller';
	protected $description = 'Protects LCP images from being lazy-loaded and adds lazy loading to iframes and videos.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only optimize the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// 1. Protect the first 2 images from lazy loading to improve LCP.
		add_filter( 'wp_omit_loading_attr_threshold', [ $this, 'set_lazy_load_threshold' ] );

		// 2. Add lazy loading to iframes and videos in post content.
		add_filter( 'the_content', [ $this, 'add_lazy_loading_to_media' ], 15 );
	}

	/**
	 * Increases the threshold for omitting the loading="lazy" attribute.
	 * 
	 * @return int Number of images to skip (defaulting to 2).
	 */
	public function set_lazy_load_threshold(): int {
		return 2;
	}

	/**
	 * Scans content for iframes and videos and injects loading="lazy".
	 * 
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function add_lazy_loading_to_media( string $content ): string {
		if ( empty( $content ) ) {
			return $content;
		}

		// Pattern targets <iframe ...> and <video ...>
		$pattern = '/<(iframe|video)([^>]+)>/i';

		return preg_replace_callback( $pattern, function( $matches ) {
			$tag        = $matches[1];
			$attributes = $matches[2];

			// If loading="lazy" or loading="eager" is already present, do nothing.
			if ( stripos( $attributes, ' loading=' ) !== false ) {
				return $matches[0];
			}

			// Inject loading="lazy" attribute.
			return "<{$tag} loading=\"lazy\"{$attributes}>";
		}, $content );
	}
}