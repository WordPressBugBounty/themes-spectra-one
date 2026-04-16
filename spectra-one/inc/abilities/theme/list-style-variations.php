<?php
/**
 * List Style Variations Ability
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
 * Class List_Style_Variations
 */
final class List_Style_Variations extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/list-style-variations';
		$this->label       = __( 'List Spectra One Style Variations', 'spectra-one' );
		$this->description = __( 'Returns all available Spectra One style variations (color schemes) with their titles and color palettes. Use this to see what visual themes are available.', 'spectra-one' );
		$this->capability  = 'edit_theme_options';
	}

	/**
	 * Get tool type.
	 *
	 * @return string
	 */
	public function get_tool_type() {
		return 'list';
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
				'variations' => array(
					'type'        => 'array',
					'description' => 'Style variations with title, slug, and color palette.',
				),
				'total'      => array(
					'type'        => 'integer',
					'description' => 'Total variations.',
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
			'list style variations',
			'show available color schemes',
			'view theme style options',
			'display color scheme variations',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$styles_dir = get_template_directory() . '/styles/';
		$variations = array();

		if ( is_dir( $styles_dir ) ) {
			$files = glob( $styles_dir . '*.json' );
			foreach ( $files as $file ) {
				$slug = basename( $file, '.json' );
				$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

				$colors = array();
				if ( isset( $data['settings']['color']['palette'] ) ) {
					$colors = array_map(
						static function ( array $color ) {
							return array(
								'name'  => esc_html( $color['name'] ?? '' ),
								'slug'  => sanitize_text_field( $color['slug'] ?? '' ),
								'color' => sanitize_text_field( $color['color'] ?? '' ),
							);
						},
						$data['settings']['color']['palette']
					);
				}

				$variations[] = array(
					'title'  => esc_html( $data['title'] ?? ucfirst( str_replace( '-', ' ', $slug ) ) ),
					'slug'   => sanitize_text_field( $slug ),
					'colors' => $colors,
				);
			}
		}

		return Response::success(
			/* translators: %d: number of variations */
			sprintf( __( 'Found %d style variations.', 'spectra-one' ), count( $variations ) ),
			array(
				'variations' => $variations,
				'total'      => count( $variations ),
			)
		);
	}
}

List_Style_Variations::register();
