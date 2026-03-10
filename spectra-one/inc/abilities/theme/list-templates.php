<?php
/**
 * List Templates Ability
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
 * Class List_Templates
 */
final class List_Templates extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/list-templates';
		$this->label       = __( 'List Spectra One Templates', 'spectra-one' );
		$this->description = __( 'Returns all templates and template parts registered by the Spectra One theme.', 'spectra-one' );
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
			'properties' => array(
				'type' => array(
					'type'        => 'string',
					'description' => 'Filter by type.',
					'enum'        => array( 'templates', 'parts', 'all' ),
					'default'     => 'all',
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
				'templates' => array(
					'type'        => 'array',
					'description' => 'Templates with slug and type (template or part).',
				),
				'total'     => array( 'type' => 'integer', 'description' => 'Total templates.' ),
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
			'list all templates',
			'show template parts',
			'view available page templates',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$type      = isset( $args['type'] ) ? sanitize_text_field( $args['type'] ) : 'all';
		$templates = array();

		if ( 'all' === $type || 'templates' === $type ) {
			$templates_dir = get_template_directory() . '/templates/';
			if ( is_dir( $templates_dir ) ) {
				$files = glob( $templates_dir . '*.html' );
				foreach ( $files as $file ) {
					$templates[] = array(
						'slug' => basename( $file, '.html' ),
						'type' => 'template',
					);
				}
			}
		}

		if ( 'all' === $type || 'parts' === $type ) {
			$parts_dir = get_template_directory() . '/parts/';
			if ( is_dir( $parts_dir ) ) {
				$files = glob( $parts_dir . '*.html' );
				foreach ( $files as $file ) {
					$templates[] = array(
						'slug' => basename( $file, '.html' ),
						'type' => 'part',
					);
				}
			}
		}

		return Response::success(
			/* translators: %d: number of templates */
			sprintf( __( 'Found %d templates.', 'spectra-one' ), count( $templates ) ),
			array(
				'templates' => $templates,
				'total'     => count( $templates ),
			)
		);
	}
}

List_Templates::register();
