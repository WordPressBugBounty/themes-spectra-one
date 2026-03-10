<?php
/**
 * Get Typography Settings Ability
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
 * Class Get_Typography_Settings
 */
final class Get_Typography_Settings extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/get-typography-settings';
		$this->label       = __( 'Get Spectra One Typography Settings', 'spectra-one' );
		$this->description = __( 'Returns the current typography configuration including body and heading font families, weights, line heights, and responsive font sizes.', 'spectra-one' );
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
				'body'     => array(
					'type'        => 'object',
					'description' => 'Body typography: font_family, font_weight, line_height, font_size (responsive).',
				),
				'headings' => array(
					'type'        => 'object',
					'description' => 'Headings typography: font_family, font_weight, line_height.',
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
			'get current typography settings',
			'show body font configuration',
			'view heading font settings',
			'display font family and size',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$settings  = \Swt\get_spectra_one_settings();
		$font_size = $settings['font-size-body'] ?? array();

		return Response::success(
			__( 'Retrieved typography settings successfully.', 'spectra-one' ),
			array(
				'body'     => array(
					'font_family' => sanitize_text_field( $settings['body-font-family'] ?? '' ),
					'font_weight' => sanitize_text_field( $settings['body-font-weight'] ?? '' ),
					'line_height' => sanitize_text_field( $settings['body-line-height'] ?? '' ),
					'font_size'   => array(
						'desktop'      => sanitize_text_field( $font_size['desktop'] ?? '' ),
						'tablet'       => sanitize_text_field( $font_size['tablet'] ?? '' ),
						'mobile'       => sanitize_text_field( $font_size['mobile'] ?? '' ),
						'desktop_unit' => sanitize_text_field( $font_size['desktop-unit'] ?? 'px' ),
					),
				),
				'headings' => array(
					'font_family' => sanitize_text_field( $settings['headings-font-family'] ?? '' ),
					'font_weight' => sanitize_text_field( $settings['headings-font-weight'] ?? '' ),
					'line_height' => sanitize_text_field( $settings['headings-line-height'] ?? '' ),
				),
			)
		);
	}
}

Get_Typography_Settings::register();
