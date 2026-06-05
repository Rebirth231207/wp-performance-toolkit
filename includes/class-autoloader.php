<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Autoloader {

	/**
	 * Namespace Prefix.
	 */
	private const NAMESPACE_PREFIX = 'WPPT\\';

	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Autoload logic for WP classes.
	 *
	 * @param string $class The fully-qualified class name.
	 */
	public function autoload( string $class ): void {
		if ( strpos( $class, self::NAMESPACE_PREFIX ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::NAMESPACE_PREFIX ) );
		$parts          = explode( '\\', $relative_class );
		
		$file = '';
		$last = array_pop( $parts );

		foreach ( $parts as $part ) {
			$file .= strtolower( $part ) . DIRECTORY_SEPARATOR;
		}

		// Convert ClassName to class-classname.php per WP standards.
		$file .= 'class-' . strtolower( str_replace( '_', '-', $last ) ) . '.php';
		$path  = WPPT_PATH . $file;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}