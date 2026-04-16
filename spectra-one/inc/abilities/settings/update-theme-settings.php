<?php
/**
 * Update Theme Settings Ability
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
 * Class Update_Theme_Settings
 */
final class Update_Theme_Settings extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/update-theme-settings';
		$this->label       = __( 'Update Spectra One Theme Settings', 'spectra-one' );
		$this->description = __( 'Updates Spectra One global theme options such as scroll-to-top toggle. Only the provided fields are updated. This modifies options visible to all site visitors. Always confirm with the user before running this — show which settings will change and their new values.', 'spectra-one' );
		$this->capability  = 'edit_theme_options';

		$this->meta = array(
			'tool_type' => 'write',
		);
	}

	/**
	 * Get tool type.
	 *
	 * @return string
	 */
	public function get_tool_type() {
		return 'write';
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
				'scroll_top' => array(
					'type'        => 'boolean',
					'description' => 'Enable or disable the scroll-to-top button.',
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
				'updated_fields' => array(
					'type'        => 'array',
					'description' => 'List of fields that were updated.',
				),
				'settings'       => array(
					'type'        => 'object',
					'description' => 'The current settings after update.',
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
			'enable scroll to top button',
			'change theme settings',
			'turn on scroll-to-top',
			'update theme options',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$options        = get_option( 'swt_theme_options', array() );
		$updated_fields = array();
		$allowed_fields = array( 'scroll_top' );

		foreach ( $allowed_fields as $field ) {
			if ( isset( $args[ $field ] ) ) {
				$options[ $field ] = (bool) $args[ $field ];
				$updated_fields[]  = $field;
			}
		}

		if ( empty( $updated_fields ) ) {
			return Response::error(
				__( 'No settings provided to update.', 'spectra-one' ),
				__( 'Provide at least one setting to update (e.g. scroll_top).', 'spectra-one' )
			);
		}

		update_option( 'swt_theme_options', $options );

		return Response::success(
			/* translators: %s: list of updated fields */
			sprintf( __( 'Theme settings updated: %s.', 'spectra-one' ), implode( ', ', $updated_fields ) ),
			array(
				'updated_fields' => $updated_fields,
				'settings'       => array(
					'scroll_top' => ! empty( $options['scroll_top'] ),
				),
			)
		);
	}
}

Update_Theme_Settings::register();
