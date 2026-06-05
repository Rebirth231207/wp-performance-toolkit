<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LCP & Critical Asset Preloader Module
 * 
 * Automatically identifies and preloads the featured image (LCP element) 
 * and warms up DNS connections for critical external domains.
 */
class Lcp_Preloader extends Abstract_Module {

	protected $id = 'lcp-preloader';
	protected $name = 'LCP & Critical Asset Preloader';
	protected $description = 'Preloads the featured image with high fetch priority and initiates DNS prefetching for common domains.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only run on the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Inject LCP Preload at the very top of the <head>.
		add_action( 'wp_head', [ $this, 'inject_lcp_preload' ], 1 );
		
		// Inject DNS Prefetch hints.
		add_action( 'wp_head', [ $this, 'inject_dns_hints' ], 2 );
	}

	/**
	 * Identifies the featured image and injects the high-priority preload tag.
	 */
	public function inject_lcp_preload(): void {
		// Only run on singular posts/pages where an LCP image is predictable.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		
		if ( ! has_post_thumbnail( $post_id ) ) {
			return;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$image_src    = wp_get_attachment_image_src( $thumbnail_id, 'full' );

		if ( ! $image_src ) {
			return;
		}

		$src    = $image_src[0];
		$srcset = wp_get_attachment_image_srcset( $thumbnail_id, 'full' );
		$sizes  = wp_get_attachment_image_sizes( $thumbnail_id, 'full' );

		// Start building the preload tag.
		// fetchpriority="high" is a modern directive to prioritize this image for LCP.
		$preload_tag = '<link rel="preload" as="image" href="' . esc_url( $src ) . '" fetchpriority="high"';

		if ( $srcset ) {
			$preload_tag .= ' imagesrcset="' . esc_attr( $srcset ) . '"';
		}

		if ( $sizes ) {
			$preload_tag .= ' imagesizes="' . esc_attr( $sizes ) . '"';
		}

		$preload_tag .= '>';

		echo "\n" . $preload_tag . "\n";
	}

	/**
	 * Injects DNS prefetch hints for common performance-heavy domains.
	 */
	public function inject_dns_hints(): void {
		$domains = [
			'fonts.googleapis.com',
			'fonts.gstatic.com',
			'www.google-analytics.com',
			'www.googletagmanager.com',
			'connect.facebook.net'
		];

		echo "<!-- WPPT DNS Prefetch -->\n";
		foreach ( $domains as $domain ) {
			echo '<link rel="dns-prefetch" href="//' . esc_attr( $domain ) . '">' . "\n";
		}
	}
}