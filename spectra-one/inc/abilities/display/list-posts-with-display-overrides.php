<?php
/**
 * List Posts With Display Overrides Ability
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
 * Class List_Posts_With_Display_Overrides
 */
final class List_Posts_With_Display_Overrides extends Ability {
	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/list-posts-with-display-overrides';
		$this->label       = __( 'List Posts With Display Overrides', 'spectra-one' );
		$this->description = __( 'Lists all posts and pages that have Spectra One display overrides (hidden header, hidden footer, sticky header, etc.). Useful for auditing which pages have custom display settings.', 'spectra-one' );
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
				'override_type' => array(
					'type'        => 'string',
					'description' => 'Filter by specific override type.',
					'enum'        => array( 'header_hidden', 'footer_hidden', 'title_hidden', 'sticky_header', 'transparent_header', 'any' ),
					'default'     => 'any',
				),
				'page'          => array(
					'type'        => 'integer',
					'description' => 'Page number.',
					'default'     => 1,
				),
				'per_page'      => array(
					'type'        => 'integer',
					'description' => 'Items per page (max 100).',
					'default'     => 20,
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
				'posts'       => array(
					'type'        => 'array',
					'description' => 'Posts with display overrides including id, title, type, edit URL, and active overrides.',
				),
				'total'       => array(
					'type'        => 'integer',
					'description' => 'Total matching posts.',
				),
				'total_pages' => array(
					'type'        => 'integer',
					'description' => 'Total pages.',
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
			'list pages with hidden header',
			'show posts with display overrides',
			'find pages with sticky header enabled',
			'audit custom display settings',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$override_type = isset( $args['override_type'] ) ? sanitize_text_field( $args['override_type'] ) : 'any';
		$page          = max( 1, isset( $args['page'] ) ? absint( $args['page'] ) : 1 );
		$per_page      = max( 1, min( 100, isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 20 ) );

		$meta_keys_map = array(
			'header_hidden'      => '_swt_meta_header_display',
			'footer_hidden'      => '_swt_meta_footer_display',
			'title_hidden'       => '_swt_meta_site_title_display',
			'sticky_header'      => '_swt_meta_sticky_header',
			'transparent_header' => '_swt_meta_transparent_header',
		);

		$meta_query = array( 'relation' => 'OR' );

		if ( 'any' === $override_type ) {
			foreach ( $meta_keys_map as $meta_key ) {
				$meta_query[] = array(
					'key'     => $meta_key,
					'value'   => '1',
					'compare' => '=',
				);
			}
		} elseif ( isset( $meta_keys_map[ $override_type ] ) ) {
			$meta_query = array(
				array(
					'key'     => $meta_keys_map[ $override_type ],
					'value'   => '1',
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'paged'          => $page,
				'posts_per_page' => $per_page,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$posts = array();
		/** @var \WP_Post $post */
		foreach ( $query->posts as $post ) {
			$edit_link = get_edit_post_link( $post->ID, 'raw' );
			$posts[]   = array(
				'id'        => intval( $post->ID ),
				'title'     => esc_html( $post->post_title ),
				'type'      => $post->post_type,
				'url_edit'  => esc_url( $edit_link ?? '' ),
				'overrides' => array(
					'hide_header'        => (bool) get_post_meta( $post->ID, '_swt_meta_header_display', true ),
					'hide_footer'        => (bool) get_post_meta( $post->ID, '_swt_meta_footer_display', true ),
					'hide_title'         => (bool) get_post_meta( $post->ID, '_swt_meta_site_title_display', true ),
					'sticky_header'      => (bool) get_post_meta( $post->ID, '_swt_meta_sticky_header', true ),
					'transparent_header' => (bool) get_post_meta( $post->ID, '_swt_meta_transparent_header', true ),
				),
			);
		}

		$total = intval( $query->found_posts );

		return Response::success(
			/* translators: %d: number of posts */
			sprintf( __( 'Found %d posts with display overrides.', 'spectra-one' ), $total ),
			array(
				'posts'       => $posts,
				'total'       => $total,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);
	}
}

List_Posts_With_Display_Overrides::register();
