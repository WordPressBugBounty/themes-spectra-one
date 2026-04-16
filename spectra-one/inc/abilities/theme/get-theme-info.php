<?php
/**
 * Get Theme Info Ability
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
 * Class Get_Theme_Info
 */
final class Get_Theme_Info extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/get-theme-info';
		$this->label       = __( 'Get Spectra One Theme Info', 'spectra-one' );
		$this->description = __( 'Returns Spectra One theme metadata including version, required WordPress/PHP versions, and Spectra plugin compatibility status. Call this first when working with the theme.', 'spectra-one' );
		$this->capability  = 'edit_theme_options';
	}

	/**
	 * Get input schema.
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(),
		);
	}

	/**
	 * Get output schema.
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return $this->build_output_schema(
			array(
				'name'                  => array(
					'type'        => 'string',
					'description' => 'Theme name.',
				),
				'version'               => array(
					'type'        => 'string',
					'description' => 'Theme version.',
				),
				'slug'                  => array(
					'type'        => 'string',
					'description' => 'Theme slug.',
				),
				'text_domain'           => array(
					'type'        => 'string',
					'description' => 'Text domain.',
				),
				'requires_wp'           => array(
					'type'        => 'string',
					'description' => 'Minimum WordPress version.',
				),
				'requires_php'          => array(
					'type'        => 'string',
					'description' => 'Minimum PHP version.',
				),
				'spectra_plugin_status' => array(
					'type'        => 'string',
					'description' => 'Spectra plugin status: activated, installed, or not-installed.',
				),
				'style_variations'      => array(
					'type'        => 'integer',
					'description' => 'Number of style variations.',
				),
				'patterns'              => array(
					'type'        => 'integer',
					'description' => 'Number of block patterns.',
				),
				'templates'             => array(
					'type'        => 'integer',
					'description' => 'Number of templates.',
				),
			)
		);
	}

	/**
	 * Get examples.
	 *
	 * @return array
	 */
	public function get_examples() {
		return array(
			'get theme information',
			'show Spectra One version',
			'check Spectra plugin status',
			'view theme details',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$theme = wp_get_theme( 'spectra-one' );

		$styles_dir      = get_template_directory() . '/styles/';
		$variation_count = is_dir( $styles_dir ) ? count( glob( $styles_dir . '*.json' ) ) : 0;

		$patterns_dir  = get_template_directory() . '/patterns/';
		$pattern_count = is_dir( $patterns_dir ) ? count( glob( $patterns_dir . '*.php' ) ) : 0;

		$templates_dir  = get_template_directory() . '/templates/';
		$template_count = is_dir( $templates_dir ) ? count( glob( $templates_dir . '*.html' ) ) : 0;

		return Response::success(
			/* translators: %s: theme version */
			sprintf( __( 'Spectra One theme v%s info retrieved.', 'spectra-one' ), $theme->get( 'Version' ) ),
			array(
				'name'                  => esc_html( $theme->get( 'Name' ) ),
				'version'               => sanitize_text_field( $theme->get( 'Version' ) ),
				'slug'                  => 'spectra-one',
				'text_domain'           => 'spectra-one',
				'requires_wp'           => sanitize_text_field( $theme->get( 'RequiresWP' ) ),
				'requires_php'          => sanitize_text_field( $theme->get( 'RequiresPHP' ) ),
				'spectra_plugin_status' => \Swt\is_spectra_plugin_status(),
				'style_variations'      => $variation_count,
				'patterns'              => $pattern_count,
				'templates'             => $template_count,
			)
		);
	}
}

Get_Theme_Info::register();
