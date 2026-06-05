<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Script Optimizer Module
 * 
 * Automatically applies defer and async attributes to enqueued scripts 
 * to eliminate render-blocking JavaScript and improve LCP/TBT scores.
 */
class Script_Optimizer extends Abstract_Module {

	protected $id = 'script-optimizer';
	protected $name = 'Script Optimizer';
	protected $description = 'Optimizes JavaScript execution by applying defer and async attributes to enqueued scripts.';

	/**
	 * List of scripts that should never be deferred or asynced.
	 * Typically scripts that provide a global API for inline code.
	 * 
	 * @var array
	 */
	private $ignore_list = [
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'wp-polyfill',
	];

	/**
	 * List of handles or keywords in URL that should use 'async'.
	 * These are usually independent third-party trackers.
	 * 
	 * @var array
	 */
	private $async_list = [
		'google-analytics',
		'gtag',
		'googletagmanager',
		'analytics.js',
		'facebook-jssdk',
		'fbevents.js',
	];

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only optimize the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Filter the HTML output of every enqueued script.
		add_filter( 'script_loader_tag', [ $this, 'apply_smart_attributes' ], 10, 3 );
	}

	/**
	 * Modifies the <script> tag to include defer or async attributes.
	 * 
	 * @param string $tag    The <script> tag HTML.
	 * @param string $handle The script identifier.
	 * @param string $src    The script source URL.
	 * @return string Modified <script> tag.
	 */
	public function apply_smart_attributes( string $tag, string $handle, string $src ): string {
		// 1. Safety check: Do not touch if it's in the ignore list.
		if ( in_array( $handle, $this->ignore_list, true ) ) {
			return $tag;
		}

		// 2. Do not touch if the script is inline (no src) or already has attributes.
		if ( ! $src || strpos( $tag, ' defer' ) !== false || strpos( $tag, ' async' ) !== false ) {
			return $tag;
		}

		// 3. Check for Async candidates (Third-party trackers).
		foreach ( $this->async_list as $keyword ) {
			if ( strpos( $handle, $keyword ) !== false || strpos( $src, $keyword ) !== false ) {
				return str_replace( '<script ', '<script async ', $tag );
			}
		}

		// 4. Default to Defer for all other frontend scripts.
		// This is the safest way to prevent render-blocking.
		return str_replace( '<script ', '<script defer ', $tag );
	}
}