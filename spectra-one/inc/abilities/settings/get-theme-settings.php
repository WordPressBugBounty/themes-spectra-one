<?php
/**
 * Get Theme Settings Ability
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
 * Class Get_Theme_Settings
 */
final class Get_Theme_Settings extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/get-theme-settings';
		$this->label       = __( 'Get Spectra One Theme Settings', 'spectra-one' );
		$this->description = __( 'Returns the current Spectra One theme settings including global options (scroll-to-top), color palette, and typography configuration. Use this to understand the active theme configuration.', 'spectra-one' );
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
				'options'    => array(
					'type'        => 'object',
					'description' => 'Global theme options including scroll_top.',
				),
				'colors'     => array(
					'type'        => 'array',
					'description' => 'Active color palette with name, slug, and hex value for each color.',
				),
				'typography' => array(
					'type'        => 'object',
					'description' => 'Typography settings for body and headings (font family, weight, line height).',
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
			'get current theme settings',
			'show Spectra One configuration',
			'view theme options and colors',
			'display active theme settings',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$options  = get_option( 'swt_theme_options', array() );
		$settings = \Swt\get_spectra_one_settings();

		$theme_json = \Swt\get_theme_json();
		$db_styles  = \Swt\get_theme_custom_styles();

		// Build color palette from user customizations or theme.json defaults.
		$colors = array();
		if ( ! empty( $db_styles['post_content'] ) && isset( $db_styles['post_content']['settings']['color']['palette']['theme'] ) ) {
			$colors = $db_styles['post_content']['settings']['color']['palette']['theme'];
		} elseif ( isset( $theme_json['settings']['color']['palette'] ) ) {
			$colors = $theme_json['settings']['color']['palette'];
		}

		return Response::success(
			__( 'Retrieved Spectra One theme settings successfully.', 'spectra-one' ),
			array(
				'options'    => array(
					'scroll_top' => ! empty( $options['scroll_top'] ),
				),
				'colors'     => array_values(
					array_map(
						static function ( array $color ) {
							return array(
								'name'  => esc_html( $color['name'] ?? '' ),
								'slug'  => sanitize_text_field( $color['slug'] ?? '' ),
								'color' => sanitize_text_field( $color['color'] ?? '' ),
							);
						},
						$colors
					)
				),
				'typography' => array(
					'body_font_family'     => sanitize_text_field( $settings['body-font-family'] ?? '' ),
					'body_font_weight'     => sanitize_text_field( $settings['body-font-weight'] ?? '' ),
					'body_line_height'     => sanitize_text_field( $settings['body-line-height'] ?? '' ),
					'headings_font_family' => sanitize_text_field( $settings['headings-font-family'] ?? '' ),
					'headings_font_weight' => sanitize_text_field( $settings['headings-font-weight'] ?? '' ),
					'headings_line_height' => sanitize_text_field( $settings['headings-line-height'] ?? '' ),
				),
			)
		);
	}
}

Get_Theme_Settings::register();
