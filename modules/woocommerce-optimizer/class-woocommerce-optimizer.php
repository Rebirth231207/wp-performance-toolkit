<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Optimizer Module
 * 
 * Surgical performance optimization for WooCommerce. Dequeues scripts/styles 
 * and disables cart fragments on non-eCommerce pages.
 */
class Woocommerce_Optimizer extends Abstract_Module {

	protected $id = 'woocommerce-optimizer';
	protected $name = 'WooCommerce Optimizer';
	protected $description = 'Disables WooCommerce bloat, scripts, and AJAX cart fragments on non-shop pages.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// 1. Dependency Check: Only run if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// 2. Only optimize the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// 3. Hook into script and style loading.
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_wc_assets' ], 999 );
		add_action( 'wp_print_styles', [ $this, 'dequeue_wc_block_styles' ], 999 );
	}

	/**
	 * Checks if the current page is a WooCommerce-related page.
	 * 
	 * @return bool True if it is a shop, product, cart, checkout, or account page.
	 */
	private function is_wc_context(): bool {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return false;
		}

		return ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
	}

	/**
	 * Dequeues WooCommerce scripts and styles on non-shop pages.
	 */
	public function dequeue_wc_assets(): void {
		// If we are on a WooCommerce page, keep everything as is.
		if ( $this->is_wc_context() ) {
			return;
		}

		// --- Dequeue Styles ---
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce_frontend_styles' );
		wp_dequeue_style( 'woocommerce-inline' );

		// --- Dequeue Scripts ---
		wp_dequeue_script( 'wc-add-to-cart' );
		wp_dequeue_script( 'woocommerce' );
		wp_dequeue_script( 'jquery-blockui' );
		wp_dequeue_script( 'jquery-placeholder' );
		wp_dequeue_script( 'jquery-cookie' );

		// --- Disable Cart Fragments (The AJAX killer) ---
		// This prevents the /?wc-ajax=get_refreshed_fragments request.
		wp_dequeue_script( 'wc-cart-fragments' );
	}

	/**
	 * Dequeues WooCommerce Gutenberg block styles on non-shop pages.
	 */
	public function dequeue_wc_block_styles(): void {
		if ( $this->is_wc_context() ) {
			return;
		}

		wp_dequeue_style( 'wc-blocks-vendors-style' );
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_style( 'wc-all-blocks-style' );
	}
}