<?php
/**
 * Update Post Display Settings Ability
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
 * Class Update_Post_Display_Settings
 */
final class Update_Post_Display_Settings extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/update-post-display-settings';
		$this->label       = __( 'Update Post Display Settings', 'spectra-one' );
		$this->description = __( 'Updates Spectra One display settings for a specific post or page. Toggle header/footer visibility, sticky header, transparent header, or page title display. Only provided fields are updated. Always confirm with the user before running this — state the post title, ID, and which fields will change. Use spectra-one/get-post-display-settings first to show current values.', 'spectra-one' );
		$this->capability  = 'edit_posts';

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
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id'            => array(
					'type'        => 'integer',
					'description' => 'The post or page ID.',
				),
				'hide_header'        => array(
					'type'        => 'boolean',
					'description' => 'Hide header on this post.',
				),
				'hide_footer'        => array(
					'type'        => 'boolean',
					'description' => 'Hide footer on this post.',
				),
				'hide_title'         => array(
					'type'        => 'boolean',
					'description' => 'Hide page title on this post.',
				),
				'sticky_header'      => array(
					'type'        => 'boolean',
					'description' => 'Enable sticky header on this post.',
				),
				'transparent_header' => array(
					'type'        => 'boolean',
					'description' => 'Enable transparent header on this post.',
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
				'post_id'        => array(
					'type'        => 'integer',
					'description' => 'The post ID.',
				),
				'updated_fields' => array(
					'type'        => 'array',
					'description' => 'Fields that were updated.',
				),
				'settings'       => array(
					'type'        => 'object',
					'description' => 'Current display settings after update.',
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
			'hide header on page 42',
			'enable sticky header on post 10',
			'disable footer for page 5',
			'make header transparent on landing page',
		);
	}

	/**
	 * Check permissions - requires edit_post for the specific post.
	 *
	 * @param \WP_REST_Request $request REST Request.
	 * @return bool|\WP_Error
	 */
	public function check_permission( $request ) {
		return current_user_can( $this->capability );
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$post_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : 0;

		if ( 0 === $post_id ) {
			return Response::error(
				__( 'Invalid post ID.', 'spectra-one' ),
				__( 'Provide a valid post or page ID.', 'spectra-one' )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return Response::error(
				/* translators: %d: post ID */
				sprintf( __( 'Post %d not found.', 'spectra-one' ), $post_id )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Response::error(
				__( 'You do not have permission to edit this post.', 'spectra-one' )
			);
		}

		$meta_map = array(
			'hide_header'        => '_swt_meta_header_display',
			'hide_footer'        => '_swt_meta_footer_display',
			'hide_title'         => '_swt_meta_site_title_display',
			'sticky_header'      => '_swt_meta_sticky_header',
			'transparent_header' => '_swt_meta_transparent_header',
		);

		$updated_fields = array();

		foreach ( $meta_map as $field => $meta_key ) {
			if ( ! isset( $args[ $field ] ) ) {
				continue;
			}

			$value = (bool) $args[ $field ];
			if ( $value ) {
				update_post_meta( $post_id, $meta_key, true );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
			$updated_fields[] = $field;
		}

		if ( empty( $updated_fields ) ) {
			return Response::error(
				__( 'No display settings provided to update.', 'spectra-one' ),
				__( 'Provide at least one of: hide_header, hide_footer, hide_title, sticky_header, transparent_header.', 'spectra-one' )
			);
		}

		return Response::success(
			/* translators: 1: post title, 2: updated fields */
			sprintf( __( 'Updated display settings for "%1$s": %2$s.', 'spectra-one' ), $post->post_title, implode( ', ', $updated_fields ) ),
			array(
				'post_id'        => intval( $post_id ),
				'updated_fields' => $updated_fields,
				'settings'       => array(
					'hide_header'        => (bool) get_post_meta( $post_id, '_swt_meta_header_display', true ),
					'hide_footer'        => (bool) get_post_meta( $post_id, '_swt_meta_footer_display', true ),
					'hide_title'         => (bool) get_post_meta( $post_id, '_swt_meta_site_title_display', true ),
					'sticky_header'      => (bool) get_post_meta( $post_id, '_swt_meta_sticky_header', true ),
					'transparent_header' => (bool) get_post_meta( $post_id, '_swt_meta_transparent_header', true ),
				),
			)
		);
	}
}

Update_Post_Display_Settings::register();
