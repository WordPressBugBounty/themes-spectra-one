<?php
/**
 * Update Navigation Ability
 *
 * Updates the FSE navigation menu with page links.
 * Creates or updates a wp_navigation post and wires the header
 * and footer template parts to reference it via the "ref" attribute.
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
 * Class Update_Navigation
 */
final class Update_Navigation extends Ability {
	/**
	 * Default header navigation block attributes when no inline nav exists
	 * in the header template and we need to seed one.
	 */
	private const HEADER_DEFAULT_ATTRS = array(
		'textColor' => 'heading',
		'layout'    => array(
			'type'           => 'flex',
			'justifyContent' => 'right',
		),
	);

	/**
	 * Default footer navigation block attributes.
	 */
	private const FOOTER_DEFAULT_ATTRS = array(
		'overlayMenu' => 'never',
		'layout'      => array(
			'type'                   => 'flex',
			'setCascadingProperties' => true,
			'justifyContent'         => 'center',
			'orientation'            => 'horizontal',
		),
	);

	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/update-navigation';
		$this->label       = __( 'Update Navigation Menu', 'spectra-one' );
		$this->description = __( 'Updates the site navigation menu with page links. Accepts an array of menu items [{label, url}]. Creates the navigation post if needed and wires the header template part to use it. For FSE block themes — uses wp:navigation-link blocks, not classic menus.', 'spectra-one' );
		$this->capability  = 'edit_theme_options';
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
				'items'  => array(
					'type'        => 'array',
					'description' => 'Array of menu items. Each item: {"label": "Page Title", "url": "/about/", "id": 123}. "id" is optional WordPress page ID for post-type links. "url" can be relative or absolute.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'label' => array(
								'type'        => 'string',
								'description' => 'Menu item display text.',
							),
							'url'   => array(
								'type'        => 'string',
								'description' => 'Link URL (relative or absolute).',
							),
							'id'    => array(
								'type'        => 'integer',
								'description' => 'Optional WordPress page/post ID for post-type links.',
							),
						),
					),
				),
				'append' => array(
					'type'        => 'boolean',
					'description' => 'If true, append items to existing navigation instead of replacing. Skips duplicates by page ID or URL.',
				),
			),
			'required'   => array( 'items' ),
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
				'navigation_id' => array(
					'type'        => 'integer',
					'description' => 'The wp_navigation post ID.',
				),
				'item_count'    => array(
					'type'        => 'integer',
					'description' => 'Number of menu items set.',
				),
				'header_wired'  => array(
					'type'        => 'boolean',
					'description' => 'Whether the header template was updated to reference this navigation.',
				),
				'footer_wired'  => array(
					'type'        => 'boolean',
					'description' => 'Whether the footer template was updated to reference this navigation.',
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
			'set navigation menu with Home, About, Contact links',
			'update site menu items',
			'create navigation for generated pages',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$items  = $args['items'] ?? array();
		$append = ! empty( $args['append'] );

		if ( empty( $items ) || ! is_array( $items ) ) {
			return Response::error(
				__( 'At least one menu item is required.', 'spectra-one' ),
				__( 'Provide items as [{"label": "Home", "url": "/"}].', 'spectra-one' )
			);
		}

		if ( $append ) {
			$items = $this->merge_with_existing( $items );
		}

		$blocks = $this->build_navigation_link_blocks( $items );
		if ( empty( $blocks ) ) {
			return Response::error(
				__( 'No valid menu items provided.', 'spectra-one' ),
				__( 'Each item needs at least a "label".', 'spectra-one' )
			);
		}

		$nav_id = $this->get_or_create_navigation_post();
		if ( is_wp_error( $nav_id ) ) {
			return Response::from_wp_error( $nav_id );
		}

		$update = wp_update_post(
			array(
				'ID'           => $nav_id,
				'post_content' => implode( "\n", $blocks ),
			),
			true
		);

		if ( is_wp_error( $update ) ) {
			return Response::from_wp_error( $update );
		}

		$header_wired = $this->wire_navigation( 'header', $nav_id, self::HEADER_DEFAULT_ATTRS );
		$footer_wired = $this->wire_navigation( 'footer', $nav_id, self::FOOTER_DEFAULT_ATTRS );

		return Response::success(
			/* translators: %d: number of menu items */
			sprintf( __( 'Navigation updated with %d menu items.', 'spectra-one' ), count( $blocks ) ),
			array(
				'navigation_id' => $nav_id,
				'item_count'    => count( $blocks ),
				'header_wired'  => $header_wired,
				'footer_wired'  => $footer_wired,
			)
		);
	}

	/**
	 * Build wp:navigation-link block markup for each valid item.
	 *
	 * @param array<int, array<string, mixed>> $items Raw items.
	 * @return array<int, string> Serialized block markup.
	 */
	private function build_navigation_link_blocks( array $items ): array {
		$blocks = array();
		foreach ( $items as $item ) {
			$label = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
			if ( '' === $label ) {
				continue;
			}

			$attrs = array(
				'label'          => $label,
				'url'            => esc_url( (string) ( $item['url'] ?? '#' ) ),
				'isTopLevelLink' => true,
			);

			$id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			if ( $id > 0 ) {
				$attrs['kind'] = 'post-type';
				$attrs['id']   = $id;
			} else {
				$attrs['kind'] = 'custom';
			}

			$blocks[] = '<!-- wp:navigation-link ' . Helpers::safe_json_encode( $attrs ) . ' /-->';
		}

		return $blocks;
	}

	/**
	 * Find existing wp_navigation post or create one.
	 *
	 * @return int|\WP_Error Navigation post ID.
	 */
	private function get_or_create_navigation_post() {
		$existing = get_posts(
			array(
				'post_type'   => 'wp_navigation',
				'post_status' => 'publish',
				'numberposts' => 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		if ( ! empty( $existing ) ) {
			return $existing[0]->ID;
		}

		return wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_title'   => 'Navigation',
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);
	}

	/**
	 * Wire a template part (header/footer) so that its wp:navigation block
	 * references the given navigation post via the "ref" attribute.
	 *
	 * Handles three shapes of template part content:
	 *  1. A DB override with inline blocks including core/navigation — ideal case.
	 *  2. A DB override whose only block is a core/pattern reference — common for
	 *     header/footer that the user hasn't edited yet; we expand the pattern
	 *     inline so we can find and wire its navigation block.
	 *  3. No DB override (template is still from the theme files) — we expand
	 *     the referenced pattern and create the DB override on the fly.
	 *
	 * @param string $area          Template part slug (header/footer).
	 * @param int    $nav_id        Navigation post ID.
	 * @param array  $default_attrs Attributes to seed a new nav block.
	 * @return bool Whether the template part was updated (or already correct).
	 */
	private function wire_navigation( string $area, int $nav_id, array $default_attrs ): bool {
		$template = get_block_template( get_stylesheet() . '//' . $area, 'wp_template_part' );
		if ( ! $template ) {
			return false;
		}

		$content = $template->content;
		$parsed  = parse_blocks( $content );

		// If the part is just a wp:pattern reference (no DB override yet, or a
		// DB override that was itself written as a pattern ref), expand it so
		// the navigation block inside the pattern is reachable.
		/** @psalm-suppress InvalidScalarArgument -- parse_blocks() shape is compatible with the looser array<int, array<string, mixed>> expected here. */
		$parsed = $this->expand_pattern_references( $parsed );

		if ( $this->navigation_already_wired( $parsed, $nav_id ) ) {
			// If an expansion happened but the ref was already correct in the
			// rendered pattern, still persist the expanded content so the area
			// stops rendering a stale pattern lookup on each request.
			/** @psalm-suppress ArgumentTypeCoercion -- $parsed retains parse_blocks() shape through expand_pattern_references. */
			return $this->persist_template_part_content( $template, $area, serialize_blocks( $parsed ), $content );
		}

		if ( ! $this->set_navigation_ref( $parsed, $nav_id, $default_attrs ) ) {
			return false;
		}

		/** @psalm-suppress ArgumentTypeCoercion -- $parsed retains parse_blocks() shape through expand_pattern_references. */
		return $this->persist_template_part_content( $template, $area, serialize_blocks( $parsed ), $content );
	}

	/**
	 * Expand any top-level core/pattern block references into their registered
	 * block content. Non-pattern blocks pass through untouched.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks from parse_blocks().
	 * @return array<int, array<string, mixed>> Expanded block list.
	 */
	private function expand_pattern_references( array $blocks ): array {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$result   = array();

		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] ?? '' ) !== 'core/pattern' ) {
				$result[] = $block;
				continue;
			}

			$slug = (string) ( $block['attrs']['slug'] ?? '' );
			if ( '' === $slug || ! $registry->is_registered( $slug ) ) {
				$result[] = $block;
				continue;
			}

			$pattern = $registry->get_registered( $slug );
			$content = isset( $pattern['content'] ) ? (string) $pattern['content'] : '';
			if ( '' === $content ) {
				$result[] = $block;
				continue;
			}

			$expanded = parse_blocks( $content );
			foreach ( $expanded as $child ) {
				$result[] = $child;
			}
		}

		return $result;
	}

	/**
	 * Persist new template part content, creating a DB override when the part
	 * is still purely a theme-file template.
	 *
	 * @param \WP_Block_Template|object $template    Template part object.
	 * @param string                    $area        header|footer.
	 * @param string                    $new_content Serialized blocks to save.
	 * @param string                    $old_content Previous content (no write when identical).
	 * @return bool
	 */
	private function persist_template_part_content( $template, string $area, string $new_content, string $old_content ): bool {
		if ( $new_content === $old_content ) {
			return ! empty( $template->wp_id );
		}

		if ( ! empty( $template->wp_id ) ) {
			$result = wp_update_post(
				array(
					'ID'           => (int) $template->wp_id,
					'post_content' => $new_content,
				),
				true
			);
			return ! is_wp_error( $result );
		}

		$inserted = wp_insert_post(
			array(
				'post_type'    => 'wp_template_part',
				'post_name'    => $area,
				'post_title'   => ucfirst( $area ),
				'post_status'  => 'publish',
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $inserted ) ) {
			return false;
		}

		wp_set_object_terms( $inserted, get_stylesheet(), 'wp_theme' );
		wp_set_object_terms( $inserted, $area, 'wp_template_part_area' );
		return true;
	}

	/**
	 * Check whether any parsed wp:navigation block already references the nav post.
	 *
	 * @param array $blocks Parsed blocks from parse_blocks().
	 * @param int   $nav_id Navigation post ID.
	 * @return bool
	 */
	private function navigation_already_wired( array $blocks, int $nav_id ): bool {
		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] ?? '' ) === 'core/navigation'
				&& isset( $block['attrs']['ref'] )
				&& (int) $block['attrs']['ref'] === $nav_id
			) {
				return true;
			}

			$inner = $block['innerBlocks'] ?? array();
			if ( is_array( $inner ) && ! empty( $inner ) && $this->navigation_already_wired( $inner, $nav_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Walk parsed blocks and set the "ref" attribute on the first wp:navigation
	 * block found. Returns true when a block was updated.
	 *
	 * @param array $blocks        Parsed blocks (by reference).
	 * @param int   $nav_id        Navigation post ID.
	 * @param array $default_attrs Default attrs when block has none.
	 * @return bool
	 */
	private function set_navigation_ref( array &$blocks, int $nav_id, array $default_attrs ): bool {
		foreach ( $blocks as &$block ) {
			if ( ( $block['blockName'] ?? '' ) === 'core/navigation' ) {
				$existing_attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
				if ( empty( $existing_attrs ) ) {
					$existing_attrs = $default_attrs;
				}

				$existing_attrs['ref'] = $nav_id;
				$block['attrs']        = $existing_attrs;
				$block['innerBlocks']  = array();
				$block['innerHTML']    = '';
				$block['innerContent'] = array();
				return true;
			}

			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				if ( $this->set_navigation_ref( $block['innerBlocks'], $nav_id, $default_attrs ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Merge new items with existing navigation items.
	 *
	 * Reads the current wp_navigation post, parses existing navigation-link
	 * blocks via parse_blocks(), and appends new items that don't already
	 * exist (by ID or URL).
	 *
	 * @param array $new_items Items to append.
	 * @return array Merged items (existing + new, deduplicated).
	 */
	private function merge_with_existing( array $new_items ): array {
		$nav_id = $this->get_or_create_navigation_post();
		if ( is_wp_error( $nav_id ) ) {
			return $new_items;
		}

		$nav_post = get_post( $nav_id );
		if ( ! $nav_post || empty( $nav_post->post_content ) ) {
			return $new_items;
		}

		$existing = $this->extract_navigation_link_items( (string) $nav_post->post_content );
		if ( empty( $existing ) ) {
			return $new_items;
		}

		$existing_ids  = array();
		$existing_urls = array();
		foreach ( $existing as $item ) {
			if ( ! empty( $item['id'] ) ) {
				$existing_ids[ $item['id'] ] = true;
			}
			$existing_urls[ untrailingslashit( $item['url'] ) ] = true;
		}

		foreach ( $new_items as $item ) {
			$id  = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$url = untrailingslashit( (string) ( $item['url'] ?? '' ) );

			if ( $id > 0 && isset( $existing_ids[ $id ] ) ) {
				continue;
			}
			if ( '' !== $url && isset( $existing_urls[ $url ] ) ) {
				continue;
			}

			$existing[] = $item;
		}

		return $existing;
	}

	/**
	 * Extract {label, url, id} entries from serialized navigation-link blocks.
	 *
	 * @param string $content Navigation post content.
	 * @return array<int, array{label: string, url: string, id: int}>
	 */
	private function extract_navigation_link_items( string $content ): array {
		$items  = array();
		$blocks = parse_blocks( $content );

		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] ?? '' ) !== 'core/navigation-link' ) {
				continue;
			}

			$attrs = $block['attrs'] ?? array();
			$label = sanitize_text_field( (string) ( $attrs['label'] ?? '' ) );
			if ( '' === $label ) {
				continue;
			}

			$items[] = array(
				'label' => $label,
				'url'   => (string) ( $attrs['url'] ?? '#' ),
				'id'    => isset( $attrs['id'] ) ? (int) $attrs['id'] : 0,
			);
		}

		return $items;
	}
}

Update_Navigation::register();
