<?php
/**
 * Get Pattern Markup Ability
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
 * Class Get_Pattern_Markup
 */
final class Get_Pattern_Markup extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/get-pattern-markup';
		$this->label       = __( 'Get Spectra One Pattern Markup', 'spectra-one' );
		$this->description = __( 'Returns the block markup for a specific Spectra One pattern by slug. Use this to get the block editor content for inserting into a page or post.', 'spectra-one' );
		$this->capability  = 'edit_posts';
	}

	/**
	 * Get input schema.
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return array(
			'type'       => 'object',
			'required'   => array( 'slug' ),
			'properties' => array(
				'slug' => array(
					'type'        => 'string',
					'description' => 'The pattern slug (e.g., "spectra-one/hero-banner").',
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
				'title'      => array( 'type' => 'string', 'description' => 'Pattern title.' ),
				'slug'       => array( 'type' => 'string', 'description' => 'Pattern slug.' ),
				'content'    => array( 'type' => 'string', 'description' => 'Block markup content ready to insert.' ),
				'categories' => array( 'type' => 'array', 'description' => 'Pattern categories.' ),
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
			'get markup for hero-banner pattern',
			'show content of pricing pattern',
			'get block content for spectra-one/contact',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		if ( empty( $args['slug'] ) ) {
			return Response::error(
				__( 'Pattern slug is required.', 'spectra-one' ),
				__( 'Provide a slug like "spectra-one/hero-banner". Use list-patterns to see available slugs.', 'spectra-one' )
			);
		}

		$slug     = sanitize_text_field( $args['slug'] );
		$registry = \WP_Block_Patterns_Registry::get_instance();

		if ( ! $registry->is_registered( $slug ) ) {
			return Response::error(
				/* translators: %s: pattern slug */
				sprintf( __( 'Pattern "%s" not found.', 'spectra-one' ), $slug ),
				__( 'Use spectra-one/list-patterns to see all available pattern slugs.', 'spectra-one' )
			);
		}

		$pattern = $registry->get_registered( $slug );

		return Response::success(
			/* translators: %s: pattern title */
			sprintf( __( 'Retrieved pattern markup for "%s".', 'spectra-one' ), $pattern['title'] ?? $slug ),
			array(
				'title'      => esc_html( $pattern['title'] ?? '' ),
				'slug'       => $slug,
				'content'    => $pattern['content'] ?? '',
				'categories' => isset( $pattern['categories'] ) ? array_values( $pattern['categories'] ) : array(),
			)
		);
	}
}

Get_Pattern_Markup::register();
