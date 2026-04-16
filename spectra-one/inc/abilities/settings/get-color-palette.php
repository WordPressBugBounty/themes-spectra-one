<?php
/**
 * Get Color Palette Ability
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
 * Class Get_Color_Palette
 */
final class Get_Color_Palette extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/get-color-palette';
		$this->label       = __( 'Get Spectra One Color Palette', 'spectra-one' );
		$this->description = __( 'Returns the active Spectra One color palette including any user customizations. Shows all theme colors: primary, secondary, heading, body, background, tertiary, quaternary, surface, foreground, outline, and neutral.', 'spectra-one' );
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
				'colors' => array(
					'type'        => 'array',
					'description' => 'Color palette entries with name, slug, and hex value.',
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
			'get current color palette',
			'show theme colors',
			'view active color scheme',
			'display color palette values',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$theme_json = \Swt\get_theme_json();
		$db_styles  = \Swt\get_theme_custom_styles();

		$colors     = array();
		$db_palette = $db_styles['post_content']['settings']['color']['palette'] ?? array();

		// The update-color-palette ability writes to settings.color.palette.custom,
		// which is the origin WordPress uses for user-added colors in the editor.
		// Prefer custom over theme when both are present so writes are immediately
		// visible on subsequent reads.
		if ( is_array( $db_palette ) && ! empty( $db_palette['custom'] ) && is_array( $db_palette['custom'] ) ) {
			$colors = $db_palette['custom'];
		} elseif ( is_array( $db_palette ) && ! empty( $db_palette['theme'] ) && is_array( $db_palette['theme'] ) ) {
			$colors = $db_palette['theme'];
		} elseif ( isset( $theme_json['settings']['color']['palette'] ) && is_array( $theme_json['settings']['color']['palette'] ) ) {
			$colors = $theme_json['settings']['color']['palette'];
		}

		$formatted = array_values(
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
		);

		return Response::success(
			/* translators: %d: number of colors */
			sprintf( __( 'Retrieved color palette with %d colors.', 'spectra-one' ), count( $formatted ) ),
			array(
				'colors' => $formatted,
			)
		);
	}
}

Get_Color_Palette::register();
