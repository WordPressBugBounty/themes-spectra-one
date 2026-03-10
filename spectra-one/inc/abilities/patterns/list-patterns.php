<?php
/**
 * List Patterns Ability
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
 * Class List_Patterns
 */
final class List_Patterns extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/list-patterns';
		$this->label       = __( 'List Spectra One Block Patterns', 'spectra-one' );
		$this->description = __( 'Returns all available Spectra One block patterns with their titles, slugs, categories, and keywords. Use this to discover what design patterns are available for building pages.', 'spectra-one' );
		$this->capability  = 'edit_posts';
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
			'properties' => array(
				'category' => array(
					'type'        => 'string',
					'description' => 'Filter by pattern category slug. Leave empty for all patterns.',
				),
			),
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
				'patterns' => array(
					'type'        => 'array',
					'description' => 'Block patterns with title, slug, categories, and keywords.',
				),
				'total'    => array( 'type' => 'integer', 'description' => 'Total number of patterns.' ),
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
			'list all block patterns',
			'show available patterns',
			'find hero banner patterns',
			'list pricing page patterns',
			'show contact form patterns',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$category_filter = isset( $args['category'] ) ? sanitize_text_field( $args['category'] ) : '';
		$registry        = \WP_Block_Patterns_Registry::get_instance();
		$all_patterns    = $registry->get_all_registered();

		$patterns = array();
		foreach ( $all_patterns as $pattern ) {
			$slug = $pattern['slug'] ?? ( $pattern['name'] ?? '' );
			if ( strpos( $slug, 'spectra-one/' ) !== 0 ) {
				continue;
			}

			$categories = isset( $pattern['categories'] ) ? array_values( $pattern['categories'] ) : array();

			if ( ! empty( $category_filter ) && ! in_array( $category_filter, $categories, true ) ) {
				continue;
			}

			$patterns[] = array(
				'title'      => esc_html( $pattern['title'] ?? '' ),
				'slug'       => sanitize_text_field( $slug ),
				'categories' => $categories,
				'keywords'   => isset( $pattern['keywords'] ) ? array_map( 'sanitize_text_field', $pattern['keywords'] ) : array(),
			);
		}

		return Response::success(
			/* translators: %d: number of patterns */
			sprintf( __( 'Found %d Spectra One patterns.', 'spectra-one' ), count( $patterns ) ),
			array(
				'patterns' => $patterns,
				'total'    => count( $patterns ),
			)
		);
	}
}

List_Patterns::register();
