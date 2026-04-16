<?php
/**
 * List Hooks Ability
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
 * Class List_Hooks
 */
final class List_Hooks extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/list-hooks';
		$this->label       = __( 'List Spectra One Hooks', 'spectra-one' );
		$this->description = __( 'Returns custom action hooks and filters provided by the Spectra One theme via do_action and apply_filters. These are extension points that developers and child themes can hook into. Each hook includes its name, type, accepted parameters, default value, file location, and description.', 'spectra-one' );
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
					'enum'        => array( 'action', 'filter', 'all' ),
					'description' => 'Filter by hook type. Defaults to all.',
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
				'hooks' => array(
					'type'        => 'array',
					'description' => 'List of hooks with name, type, parameters, default value, file, and description.',
				),
				'total' => array(
					'type'        => 'integer',
					'description' => 'Total number of hooks returned.',
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
			'list all theme hooks',
			'show available filters',
			'what extension points does the theme provide',
			'list Spectra One do_action and apply_filters hooks',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$type_filter = isset( $args['type'] ) ? sanitize_text_field( $args['type'] ) : 'all';
		$all_hooks   = $this->get_hooks_registry();

		if ( 'all' !== $type_filter ) {
			$all_hooks = array_values(
				array_filter(
					$all_hooks,
					static function ( array $hook ) use ( $type_filter ) {
						return isset( $hook['type'] ) && $hook['type'] === $type_filter;
					}
				)
			);
		}

		return Response::success(
			/* translators: %d: number of hooks */
			sprintf( __( 'Found %d Spectra One hooks.', 'spectra-one' ), count( $all_hooks ) ),
			array(
				'hooks' => $all_hooks,
				'total' => count( $all_hooks ),
			)
		);
	}

	/**
	 * Get the complete hooks registry.
	 *
	 * Returns only custom hooks defined by the theme via do_action() and apply_filters().
	 *
	 * @return array List of hook definitions.
	 */
	private function get_hooks_registry() {
		return array(
			array(
				'name'          => 'swt_dynamic_theme_css',
				'type'          => 'filter',
				'parameters'    => array( 'css' ),
				'default_value' => "''",
				'file'          => 'inc/scripts.php',
				'description'   => __( 'Filters the dynamic inline CSS added to the frontend. Extensions append CSS for sticky header, scroll-to-top, navigation, responsive blocks, etc.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_dynamic_theme_js',
				'type'          => 'filter',
				'parameters'    => array( 'js' ),
				'default_value' => "''",
				'file'          => 'inc/scripts.php',
				'description'   => __( 'Filters the dynamic inline JavaScript added to the frontend. Extensions append JS for sticky header, scroll-to-top, admin bar spacing, etc.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_enqueue_frontend_scripts',
				'type'          => 'filter',
				'parameters'    => array( 'enqueue' ),
				'default_value' => 'true',
				'file'          => 'inc/scripts.php',
				'description'   => __( 'Controls whether frontend scripts and styles are enqueued. Return false to completely disable theme frontend assets.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_enqueue_editor_scripts',
				'type'          => 'filter',
				'parameters'    => array( 'enqueue' ),
				'default_value' => 'true',
				'file'          => 'inc/scripts.php',
				'description'   => __( 'Controls whether block editor scripts and styles are enqueued. Return false to disable theme editor assets.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_editor_localize',
				'type'          => 'filter',
				'parameters'    => array( 'data' ),
				'default_value' => 'array (is_spectra_plugin, get_screen_id, ...)',
				'file'          => 'inc/scripts.php',
				'description'   => __( 'Filters the localized data array passed to the block editor script. Contains screen ID, Spectra plugin status, and other editor context.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_languages_directory',
				'type'          => 'filter',
				'parameters'    => array( 'lang_dir' ),
				'default_value' => 'SWT_DIR . "languages"',
				'file'          => 'inc/scripts.php',
				'description'   => __( 'Filters the path to the theme languages directory for loading translations.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_svg_icons',
				'type'          => 'filter',
				'parameters'    => array( 'icons' ),
				'default_value' => 'array (parsed from assets/svg/svgs.json)',
				'file'          => 'inc/utilities/helpers.php',
				'description'   => __( 'Filters the SVG icons array used by the theme. Add, remove, or modify available SVG icons.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_modal_menu_center',
				'type'          => 'filter',
				'parameters'    => array( 'center' ),
				'default_value' => 'true',
				'file'          => 'inc/blocks/navigation.php',
				'description'   => __( 'Controls whether the mobile modal navigation menu content is centered. Return false to disable centering.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_spctr_editor_block_spacing',
				'type'          => 'filter',
				'parameters'    => array( 'spacing' ),
				'default_value' => '15',
				'file'          => 'inc/compatibility/spectra.php',
				'description'   => __( 'Filters the default block spacing value (in pixels) used in the Spectra plugin editor.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_ability_show_in_rest',
				'type'          => 'filter',
				'parameters'    => array( 'show_in_rest', 'ability_id', 'ability_instance' ),
				'default_value' => 'true',
				'file'          => 'inc/abilities/ability.php',
				'description'   => __( 'Controls whether a specific Spectra One ability is visible in the REST API. Receives the ability ID and instance as additional parameters.', 'spectra-one' ),
			),
			array(
				'name'          => 'swt_ability_mcp_public',
				'type'          => 'filter',
				'parameters'    => array( 'is_public', 'ability_id', 'ability_instance' ),
				'default_value' => 'true',
				'file'          => 'inc/abilities/ability.php',
				'description'   => __( 'Controls whether a specific Spectra One ability is publicly exposed via MCP (Model Context Protocol). Receives the ability ID and instance as additional parameters.', 'spectra-one' ),
			),
		);
	}
}

List_Hooks::register();
