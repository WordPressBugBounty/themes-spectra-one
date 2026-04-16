<?php
/**
 * Get Post Display Settings Ability
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
 * Class Get_Post_Display_Settings
 */
final class Get_Post_Display_Settings extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/get-post-display-settings';
		$this->label       = __( 'Get Post Display Settings', 'spectra-one' );
		$this->description = __( 'Returns the Spectra One display settings for a specific post or page, including header/footer visibility, sticky header, transparent header, and title display toggles.', 'spectra-one' );
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
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The post or page ID.',
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
				'post_id'            => array(
					'type'        => 'integer',
					'description' => 'The post ID.',
				),
				'title'              => array(
					'type'        => 'string',
					'description' => 'The post title.',
				),
				'hide_header'        => array(
					'type'        => 'boolean',
					'description' => 'Whether header is hidden.',
				),
				'hide_footer'        => array(
					'type'        => 'boolean',
					'description' => 'Whether footer is hidden.',
				),
				'hide_title'         => array(
					'type'        => 'boolean',
					'description' => 'Whether page title is hidden.',
				),
				'sticky_header'      => array(
					'type'        => 'boolean',
					'description' => 'Whether sticky header is enabled.',
				),
				'transparent_header' => array(
					'type'        => 'boolean',
					'description' => 'Whether transparent header is enabled.',
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
			'get display settings for post 42',
			'check if header is hidden on page 10',
			'view post display overrides',
		);
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

		// Per-object check: contributors can only access their own posts, not others' drafts/private posts.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Response::error(
				__( 'You do not have permission to view this post\'s settings.', 'spectra-one' )
			);
		}

		return Response::success(
			/* translators: %s: post title */
			sprintf( __( 'Retrieved display settings for "%s".', 'spectra-one' ), $post->post_title ),
			array(
				'post_id'            => intval( $post->ID ),
				'title'              => esc_html( $post->post_title ),
				'hide_header'        => (bool) get_post_meta( $post_id, '_swt_meta_header_display', true ),
				'hide_footer'        => (bool) get_post_meta( $post_id, '_swt_meta_footer_display', true ),
				'hide_title'         => (bool) get_post_meta( $post_id, '_swt_meta_site_title_display', true ),
				'sticky_header'      => (bool) get_post_meta( $post_id, '_swt_meta_sticky_header', true ),
				'transparent_header' => (bool) get_post_meta( $post_id, '_swt_meta_transparent_header', true ),
			)
		);
	}
}

Get_Post_Display_Settings::register();
