<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Script_Optimizer extends Abstract_Module {

	protected $id = 'script-optimizer';
	protected $name = 'Script Optimizer';

	private $ignore_list = [ 'jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill' ];
	private $async_list  = [ 'google-analytics', 'gtag', 'googletagmanager', 'analytics.js' ];

	public function run(): void {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}
		// Priority 10: Run before Delay Manager.
		add_filter( 'script_loader_tag', [ $this, 'apply_smart_attributes' ], 10, 3 );
	}

	public function apply_smart_attributes( string $tag, string $handle, string $src ): string {
		if ( in_array( $handle, $this->ignore_list, true ) || ! $src ) {
			return $tag;
		}

		if ( strpos( $tag, ' defer' ) !== false || strpos( $tag, ' async' ) !== false ) {
			return $tag;
		}

		foreach ( $this->async_list as $keyword ) {
			if ( strpos( $handle, $keyword ) !== false || strpos( $src, $keyword ) !== false ) {
				return str_replace( '<script ', '<script async ', $tag );
			}
		}

		return str_replace( '<script ', '<script defer ', $tag );
	}
}