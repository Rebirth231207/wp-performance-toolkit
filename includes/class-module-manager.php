<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Manager
 * 
 * Handles discovery and instantiation of all toolkit modules using recursive scanning.
 */
class Module_Manager {

	/** @var array List of instantiated modules */
	private $modules = [];

	public function __construct() {
		// Priority 5 to ensure modules are ready before other plugin logic.
		add_action( 'plugins_loaded', [ $this, 'init_modules' ], 5 );
	}

	/**
	 * Discover and register all toolkit modules recursively.
	 */
	public function init_modules(): void {
		$modules_dir = WPPT_PATH . 'modules/';
		
		if ( ! is_dir( $modules_dir ) ) {
			return;
		}

		// Use Recursive Directory Iterator to find all class-*.php files.
		$directory = new \RecursiveDirectoryIterator( $modules_dir );
		$iterator  = new \RecursiveIteratorIterator( $directory );
		$regex     = new \RegexIterator( $iterator, '/^.+class-.+\.php$/i', \RecursiveRegexIterator::GET_MATCH );

		foreach ( $regex as $file_path_matches ) {
			$file_path = $file_path_matches[0];
			$file_name = basename( $file_path );
			
			// Extract Class Name from file name: class-asset-optimizer.php -> Asset_Optimizer
			$class_part = str_replace( [ 'class-', '.php' ], '', $file_name );
			$class_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $class_part ) ) );
			
			$fqcn = "\\WPPT\\Modules\\" . $class_name;
			
			if ( class_exists( $fqcn ) ) {
				$instance = new $fqcn();
				$this->register_module( $instance );
			}
		}

		$this->run_active_modules();
	}

	/**
	 * Add a module to the registry.
	 */
	public function register_module( Abstract_Module $module ): void {
		$this->modules[ $module->get_id() ] = $module;
	}

	/**
	 * Iterate through the registry and execute active modules.
	 */
	private function run_active_modules(): void {
		foreach ( $this->modules as $module ) {
			if ( $module->is_active() ) {
				$module->run();
			}
		}
	}

	/**
	 * Get all registered modules.
	 */
	public function get_modules(): array {
		return $this->modules;
	}
}