<?php
/**
 * Abilities Init
 *
 * Main initialization class for Spectra One Abilities API integration.
 * Registers the 'spectra-one' category and loads all ability classes.
 *
 * @package Spectra One
 * @subpackage Abilities
 * @since x.x.x
 */

declare( strict_types=1 );

namespace Swt\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 */
final class Init {
	/**
	 * Instance of this class.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Whether abilities have been registered.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Whether categories have been registered.
	 *
	 * @var bool
	 */
	private $categories_registered = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Support both pre-6.9 and 6.9+ (core) action names.
		add_action( 'abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );

		add_action( 'abilities_api_init', array( $this, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register ability categories.
	 *
	 * @return void
	 */
	public function register_categories(): void {
		if ( $this->categories_registered ) {
			return;
		}

		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'spectra-one',
			array(
				'label'       => __( 'Spectra One Theme', 'spectra-one' ),
				'description' => __( 'Abilities for the Spectra One theme including settings, patterns, display controls, and style variations.', 'spectra-one' ),
			)
		);

		$this->categories_registered = true;
	}

	/**
	 * Register all Spectra One abilities.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		if ( $this->registered ) {
			return;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$abilities_dir = \Swt\SWT_DIR . 'inc/abilities/';

		$ability_files = array(
			// Settings abilities.
			'settings/get-theme-settings',
			'settings/update-theme-settings',
			'settings/get-color-palette',
			'settings/get-typography-settings',

			// Pattern abilities.
			'patterns/list-patterns',
			'patterns/get-pattern-markup',
			'patterns/list-pattern-categories',

			// Display control abilities.
			'display/get-post-display-settings',
			'display/update-post-display-settings',
			'display/list-posts-with-display-overrides',

			// Theme info & style variation abilities.
			'theme/get-theme-info',
			'theme/list-style-variations',
			'theme/list-templates',
			'theme/list-hooks',
		);

		foreach ( $ability_files as $file ) {
			require_once $abilities_dir . $file . '.php';
		}

		$this->registered = true;
	}
}
