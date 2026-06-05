<?php
namespace WPPT\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Module Class.
 * 
 * All performance modules (e.g., Asset_Optimizer, Database_Optimizer) 
 * MUST extend this class.
 */
abstract class Abstract_Module {

	/** @var string Unique ID for the module. */
	protected $id;

	/** @var string Human-readable name. */
	protected $name;

	/** @var string Short description of what the module optimizes. */
	protected $description;

	/**
	 * Every module must implement its own execution logic.
	 */
	abstract public function run(): void;

	/**
	 * Get the module's unique ID.
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
	 * Check if the module is active.
	 * In a full implementation, this would check a WordPress option.
	 */
	public function is_active(): bool {
		$enabled_modules = get_option( 'wppt_enabled_modules', [] );
		
		// If no settings exist yet, we default to true for a "Zero-Config" experience.
		if ( empty( $enabled_modules ) ) {
			return true;
		}

		return isset( $enabled_modules[ $this->id ] ) ? (bool) $enabled_modules[ $this->id ] : true;
	}
}