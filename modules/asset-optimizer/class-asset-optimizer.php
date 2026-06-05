<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset Optimizer Module
 * 
 * Conditionally dequeues unnecessary CSS and JS assets to improve performance.
 */
class Asset_Optimizer extends Abstract_Module {

	protected $id = 'asset-optimizer';
	protected $name = 'Asset Optimizer';
	protected $description = 'Prevents unnecessary scripts and styles from loading on non-relevant pages.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only optimize the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Run at the latest possible priority to ensure we catch all enqueued assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'optimize_assets' ], 9999 );
		add_action( 'wp_print_footer_scripts', [ $this, 'optimize_assets' ], 9 );
	}

	/**
	 * Identify and dequeue unnecessary assets.
	 */
	public function optimize_assets(): void {
		$this->dequeue_woocommerce_bloat();
		$this->dequeue_contact_form_7_bloat();
		$this->dequeue_wp_core_bloat();
	}

	/**
	 * Removes WooCommerce assets from non-eCommerce pages.
	 */
	private function dequeue_woocommerce_bloat(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Check if we are NOT on a WooCommerce related page.
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
			
			// Styles
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-smallscreen' );
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-inline' );

			// Scripts
			wp_dequeue_script( 'wc-add-to-cart' );
			wp_dequeue_script( 'woocommerce' );
			wp_dequeue_script( 'wc-cart-fragments' );
			wp_dequeue_script( 'wc-single-product' );
			wp_dequeue_script( 'jquery-blockui' );
			wp_dequeue_script( 'jquery-placeholder' );
		}
	}

	/**
	 * Removes Contact Form 7 assets if the page does not contain a form.
	 */
	private function dequeue_contact_form_7_bloat(): void {
		global $post;

		if ( ! $post || ! class_exists( 'WPCF7' ) ) {
			return;
		}

		// Search content for the CF7 shortcode.
		$has_cf7 = false;
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'contact-form-7' ) ) {
			$has_cf7 = true;
		}

		if ( ! $has_cf7 ) {
			wp_dequeue_script( 'contact-form-7' );
			wp_dequeue_style( 'contact-form-7' );
		}
	}
	/**
	 * Removes common WordPress core bloat that is often unused.
	 */
	private function dequeue_wp_core_bloat(): void {
		// Remove Global Styles / Block Library CSS if not using block editor on current page
		// Only do this if you are strictly using a classic theme or custom build.
		// For maximum safety, we leave block styles alone, but we definitely remove Emojis.
		
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		// Dequeue Gutenberg Block Library CSS on non-singular pages (like archives)
		if ( ! is_singular() ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'wc-block-style' ); // WooCommerce blocks
		}
	}
}