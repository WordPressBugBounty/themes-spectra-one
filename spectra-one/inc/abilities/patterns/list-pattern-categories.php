<?php
/**
 * List Pattern Categories Ability
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
 * Class List_Pattern_Categories
 */
final class List_Pattern_Categories extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/list-pattern-categories';
		$this->label       = __( 'List Spectra One Pattern Categories', 'spectra-one' );
		$this->description = __( 'Returns all block pattern categories registered by the Spectra One theme.', 'spectra-one' );
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
				'categories' => array(
					'type'        => 'array',
					'description' => 'Pattern categories with name and label.',
				),
				'total'      => array( 'type' => 'integer', 'description' => 'Total number of categories.' ),
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
			'list pattern categories',
			'show available categories',
			'view pattern category list',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$registry       = \WP_Block_Pattern_Categories_Registry::get_instance();
		$all_categories = $registry->get_all_registered();

		$categories = array();
		foreach ( $all_categories as $category ) {
			$categories[] = array(
				'name'  => sanitize_text_field( $category['name'] ?? '' ),
				'label' => esc_html( $category['label'] ?? '' ),
			);
		}

		return Response::success(
			/* translators: %d: number of categories */
			sprintf( __( 'Found %d pattern categories.', 'spectra-one' ), count( $categories ) ),
			array(
				'categories' => $categories,
				'total'      => count( $categories ),
			)
		);
	}
}

List_Pattern_Categories::register();
