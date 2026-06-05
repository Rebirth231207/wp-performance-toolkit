<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API & Security Optimizer Module
 * 
 * Hardens the site by disabling XML-RPC, restricting REST API access, 
 * and injecting essential security headers.
 */
class Api_Optimizer extends Abstract_Module {

	protected $id = 'api-optimizer';
	protected $name = 'API & Security Optimizer';
	protected $description = 'Disables XML-RPC, restricts REST API to authorized users, and adds security headers.';

	/**
	 * REST API Route Whitelist.
	 * These namespaces remain accessible to public/unauthenticated users.
	 * 
	 * @var array
	 */
	private $rest_whitelist = [
		'contact-form-7', // Required for CF7 submissions
		'wc/store',       // Required for WooCommerce Cart/Checkout blocks
		'wc/v3',          // Required for some WooCommerce public features
		'oembed/1.0',     // Required for embedding content
	];

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// 1. XML-RPC Disabler
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', [ $this, 'remove_xmlrpc_header' ] );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );

		// 2. REST API Restrictions
		add_filter( 'rest_authentication_errors', [ $this, 'restrict_rest_api' ] );

		// 3. Security Headers
		add_action( 'send_headers', [ $this, 'add_security_headers' ] );
	}

	/**
	 * Removes the X-Pingback header from HTTP responses.
	 * 
	 * @param array $headers Existing HTTP headers.
	 * @return array Modified headers.
	 */
	public function remove_xmlrpc_header( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Restricts REST API access for unauthenticated users while respecting the whitelist.
	 * 
	 * @param mixed $result Current authentication status.
	 * @return mixed WP_Error if unauthorized, original $result otherwise.
	 */
	public function restrict_rest_api( $result ) {
		// If another filter already produced an error, return it.
		if ( ! empty( $result ) ) {
			return $result;
		}

		// Allow access if the user is logged in.
		if ( is_user_logged_in() ) {
			return $result;
		}

		// Get the current REST route.
		$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] ?? '' );

		// Hard-block User Enumeration for guests regardless of other logic.
		if ( strpos( $route, '/wp/v2/users' ) !== false ) {
			return new \WP_Error( 'rest_forbidden', __( 'User enumeration is disabled.', 'wp-performance-toolkit' ), [ 'status' => 401 ] );
		}

		// Check if the current route is in our whitelist.
		foreach ( $this->rest_whitelist as $allowed_route ) {
			if ( ! empty( $route ) && strpos( $route, $allowed_route ) !== false ) {
				return $result;
			}
		}

		// Block all other access for non-authenticated users.
		return new \WP_Error( 'rest_unauthorized', __( 'REST API access is restricted to authenticated users.', 'wp-performance-toolkit' ), [ 'status' => 401 ] );
	}

	/**
	 * Injects essential security headers via PHP.
	 */
	public function add_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		// Prevent browsers from MIME-sniffing a response away from the declared content-type.
		header( 'X-Content-Type-Options: nosniff' );

		// Prevent the site from being rendered in an iframe on other domains (Clickjacking protection).
		header( 'X-Frame-Options: SAMEORIGIN' );

		// Enables the Cross-site scripting (XSS) filter built into most modern web browsers.
		header( 'X-XSS-Protection: 1; mode=block' );

		// Policy to control how much referrer information should be included with requests.
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );

		// Instructs the browser that the site should only be accessed using HTTPS (STS).
		// We use a 1-year duration.
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}
	}
}