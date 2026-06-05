<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Manager.
 * 
 * Handles the recursive discovery, caching, and instantiation of performance modules.
 */
class Module_Manager {

	/** @var array Holds instances of active modules. */
	private $modules = [];

	public function __construct() {
		$this->init_modules();
	}

	/**
	 * Discovers and initializes all modules.
	 */
	private function init_modules(): void {
		// STEP 1: Explicitly load the Parent Class first.
		// This fixes the "Fatal error: Class Abstract_Module not found".
		$parent_class_path = WPPT_PATH . 'includes/class-abstract-module.php';
		if ( file_exists( $parent_class_path ) ) {
			require_once $parent_class_path;
		}

		// STEP 2: Handle Module Discovery (With Transient Caching).
		$module_files = get_transient( 'wppt_discovered_modules' );

		if ( false === $module_files ) {
			$module_files = $this->recursive_scan( WPPT_PATH . 'modules/' );
			// Cache for 24 hours to reduce Disk I/O. 
			// Clear this transient during development if you add new files.
			set_transient( 'wppt_discovered_modules', $module_files, DAY_IN_SECONDS );
		}

		// STEP 3: Load and Instantiate.
		foreach ( $module_files as $file_path ) {
			if ( ! file_exists( $file_path ) ) {
				continue;
			}

			// Require the module file.
			require_once $file_path;

			// Determine the class name from the file name.
			// Format: class-asset-optimizer.php -> Asset_Optimizer
			$file_name  = basename( $file_path );
			$class_part = str_replace( [ 'class-', '.php' ], '', $file_name );
			$class_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $class_part ) ) );
			
			// Construct the Fully Qualified Class Name (Namespace + Class).
			$fqcn = "\\WPPT\\Modules\\" . $class_name;

			if ( class_exists( $fqcn ) ) {
				$module_instance = new $fqcn();
				
				// Verify it's a valid module before running.
				if ( $module_instance instanceof Abstract_Module && $module_instance->is_active() ) {
					$this->modules[ $module_instance->get_id() ] = $module_instance;
					$module_instance->run();
				}
			}
		}
	}

	/**
	 * Recursively scans the modules directory for class files.
	 * 
	 * @param string $path Path to scan.
	 * @return array List of absolute file paths.
	 */
	private function recursive_scan( string $path ): array {
		$items = [];
		
		if ( ! is_dir( $path ) ) {
			return $items;
		}

		$directory = new \RecursiveDirectoryIterator( $path );
		$iterator  = new \RecursiveIteratorIterator( $directory );
		$regex     = new \RegexIterator( $iterator, '/^.+class-.+\.php$/i', \RecursiveRegexIterator::GET_MATCH );

		foreach ( $regex as $file ) {
			$items[] = $file[0];
		}

		return $items;
	}

	/**
	 * Get all loaded module instances.
	 */
	public function get_modules(): array {
		return $this->modules;
	}
}