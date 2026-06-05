<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Module_Manager {

	private $modules = [];

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init_modules' ], 5 );
	}

	public function init_modules(): void {
		// Attempt to get cached file paths to save Disk I/O.
		$module_files = get_transient( 'wppt_discovered_modules' );

		if ( false === $module_files ) {
			$module_files = [];
			$modules_dir  = WPPT_PATH . 'modules/';

			if ( is_dir( $modules_dir ) ) {
				$directory = new \RecursiveDirectoryIterator( $modules_dir );
				$iterator  = new \RecursiveIteratorIterator( $directory );
				$regex     = new \RegexIterator( $iterator, '/^.+class-.+\.php$/i', \RecursiveRegexIterator::GET_MATCH );

				foreach ( $regex as $file_path_matches ) {
					$module_files[] = $file_path_matches[0];
				}
				set_transient( 'wppt_discovered_modules', $module_files, DAY_IN_SECONDS );
			}
		}

		foreach ( $module_files as $file_path ) {
			if ( ! file_exists( $file_path ) ) continue;

			// CRITICAL FIX: Require the file manually to support nested directories.
			require_once $file_path;

			$file_name  = basename( $file_path );
			$class_part = str_replace( [ 'class-', '.php' ], '', $file_name );
			$class_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $class_part ) ) );
			$fqcn       = "\\WPPT\\Modules\\" . $class_name;

			if ( class_exists( $fqcn ) ) {
				$instance = new $fqcn();
				$this->register_module( $instance );
			}
		}

		$this->run_active_modules();
	}

	public function register_module( Abstract_Module $module ): void {
		$this->modules[ $module->get_id() ] = $module;
	}

	private function run_active_modules(): void {
		foreach ( $this->modules as $module ) {
			if ( $module->is_active() ) {
				$module->run();
			}
		}
	}

	public function get_modules(): array {
		return $this->modules;
	}
}