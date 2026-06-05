<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Module Class.
 * 
 * All performance modules must extend this class.
 */
abstract class Abstract_Module {

	/** @var string Unique ID for the module */
	protected $id;

	/** @var string Human-readable name */
	protected $name;

	/** @var string Short description */
	protected $description;

	/** @var string Current version */
	protected $version = '1.0.0';

	public function __construct() {
		// Modules can implement their own constructor logic if needed.
	}

	/**
	 * Get the module's unique identifier.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get the module's name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Check if the module is currently enabled in settings.
	 */
	public function is_active(): bool {
		$settings = get_option( 'wppt_enabled_modules', [] );
		// Default to true for new modules if not explicitly set.
		return isset( $settings[ $this->id ] ) ? (bool) $settings[ $this->id ] : true;
	}

	/**
	 * The core execution logic of the module.
	 * Must be implemented by the child class.
	 */
	abstract public function run(): void;

	/**
	 * Helper for adding module-specific settings if needed.
	 */
	public function get_settings_template(): string {
		return '';
	}
}