<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class I18n {

	/**
	 * Load the plugin text domain for translation.
	 * Supports: en_US, fa_IR (Persian RTL), fr_FR, zh_CN.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-performance-toolkit',
			false,
			dirname( WPPT_BASENAME ) . '/languages/'
		);
	}
}