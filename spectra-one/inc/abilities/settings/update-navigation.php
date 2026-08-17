<?php
/**
 * Update Navigation Ability
 *
 * Updates the FSE navigation menu with page links.
 * Targets the wp_navigation entity the header actually renders, preserves
 * every existing menu block verbatim (styling attributes included), and
 * wires the header/footer template parts only when their navigation block
 * doesn't already own a menu of its own.
 *
 * @package Spectra One
 * @subpackage Abilities
 * @since 1.2.0
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
		$this->description = __( 'Updates the site navigation menu with page links. Accepts an array of menu items [{label, url}]. Targets the menu the header renders; a header managing its menu as inline blocks is migrated verbatim into a navigation entity first. Replace mode (default) sets the menu to exactly the given items — items matched by page id or url keep their existing styling, non-menu blocks (search, social icons) are preserved; append mode keeps every existing item untouched. New items are styled to match their siblings. For FSE block themes — uses wp:navigation-link blocks, not classic menus.', 'spectra-one' );
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
					'description' => 'Array of menu items. Each item: {"label": "Page Title", "url": "/about/", "id": 123, "children": [{"label": "Web Design", "url": "/services/web"}]}. "id" is optional WordPress page ID for post-type links. "url" can be relative or absolute. When "children" is non-empty, the item is rendered as a wp:navigation-submenu with the children as inner wp:navigation-link blocks (hierarchical dropdown menu).',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'label'    => array(
								'type'        => 'string',
								'description' => 'Menu item display text.',
							),
							'url'      => array(
								'type'        => 'string',
								'description' => 'Link URL (relative or absolute).',
							),
							'id'       => array(
								'type'        => 'integer',
								'description' => 'Optional WordPress page/post ID for post-type links.',
							),
							'children' => array(
								'type'        => 'array',
								'description' => 'Optional sub-menu links. Each child: {"label": "...", "url": "..."}. When present and non-empty, the parent item renders as wp:navigation-submenu wrapping these wp:navigation-link children — a hierarchical dropdown.',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'label' => array(
											'type'        => 'string',
											'description' => 'Child menu item display text.',
										),
										'url'   => array(
											'type'        => 'string',
											'description' => 'Child link URL (relative or absolute).',
										),
									),
								),
							),
						),
					),
				),
				'append' => array(
					'type'        => 'boolean',
					'description' => 'If true, append items to existing navigation instead of replacing. Existing menu items pass through untouched at the attribute level; duplicates are skipped by page ID or URL.',
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
		$raw_items = $args['items'] ?? null;
		$items     = is_array( $raw_items ) ? $raw_items : array();
		$append    = ! empty( $args['append'] );

		$items = array_values(
			array_filter(
				$items,
				/**
				 * Keep only entries that carry a non-empty label.
				 *
				 * @param mixed $item Raw menu item candidate.
				 */
				static function ( $item ) {
					return is_array( $item ) && '' !== sanitize_text_field( (string) ( $item['label'] ?? '' ) );
				}
			)
		);
		if ( empty( $items ) ) {
			return Response::error(
				__( 'At least one menu item with a label is required.', 'spectra-one' ),
				__( 'Provide items as [{"label": "Home", "url": "/"}].', 'spectra-one' )
			);
		}

		// The menu this ability edits is the one the header actually renders.
		// Updating any other wp_navigation post would be an invisible write.
		$header_nav = $this->first_navigation_in_part( 'header' );
		$header_ref = $this->valid_navigation_ref( $header_nav );

		// A header may manage its menu as inline blocks with no entity ref —
		// the shipped theme's header patterns do exactly that, so this is the
		// DEFAULT case on a fresh install. Those blocks are migratable, not
		// untouchable: serialize them verbatim into a fresh navigation entity
		// and point the header at it. Nothing is rebuilt, so nothing is lost.
		$migrated_markup = null;
		if ( null === $header_ref && null !== $header_nav && ! empty( $header_nav['innerBlocks'] ) ) {
			/**
			 * Serialize the inline header menu verbatim for migration.
			 *
			 * @psalm-suppress ArgumentTypeCoercion -- innerBlocks retain parse_blocks() shape.
			 */
			$migrated_markup = trim( serialize_blocks( $header_nav['innerBlocks'] ) );
			$nav_id          = wp_insert_post(
				array(
					'post_type'    => 'wp_navigation',
					'post_title'   => __( 'Navigation', 'spectra-one' ),
					'post_status'  => 'publish',
					'post_content' => wp_slash( $migrated_markup ),
				),
				true
			);
		} else {
			$nav_id = null !== $header_ref ? $header_ref : $this->create_navigation_post();
		}
		if ( is_wp_error( $nav_id ) ) {
			return Response::from_wp_error( $nav_id );
		}

		$nav_post         = get_post( $nav_id );
		$existing_content = $nav_post ? (string) $nav_post->post_content : '';
		$existing_blocks  = '' === trim( $existing_content ) ? array() : parse_blocks( $existing_content );

		// New items must look like their siblings: adopt the styling classes
		// the existing menu entries carry (the GBS grammar puts ALL per-block
		// styling in className, so this is the whole styling contract).
		$inherit_top   = $this->dominant_link_classes( $existing_blocks );
		$inherit_child = $this->dominant_link_classes( $this->all_submenu_children( $existing_blocks ) );

		if ( $append ) {
			[ $content, $item_count ] = $this->append_items( $existing_blocks, $items, $inherit_top, $inherit_child );
		} else {
			[ $content, $item_count ] = $this->replace_items( $existing_blocks, $items, $inherit_top, $inherit_child );
		}

		if ( $content !== $existing_content ) {
			// wp_update_post() runs wp_unslash() on the data — without wp_slash()
			// the backslashes in JSON string escapes (quotes in labels, unicode
			// escapes) are stripped and the block attrs no longer parse.
			$update = wp_update_post(
				array(
					'ID'           => $nav_id,
					'post_content' => wp_slash( $content ),
				),
				true
			);

			if ( is_wp_error( $update ) ) {
				return Response::from_wp_error( $update );
			}
		}

		$header_wired = $this->wire_navigation( 'header', $nav_id, self::HEADER_DEFAULT_ATTRS, $migrated_markup );
		$footer_wired = $this->wire_navigation( 'footer', $nav_id, self::FOOTER_DEFAULT_ATTRS, $migrated_markup );

		return Response::success(
			/* translators: %d: number of menu items */
			sprintf( __( 'Navigation updated with %d menu items.', 'spectra-one' ), $item_count ),
			array(
				'navigation_id' => $nav_id,
				'item_count'    => $item_count,
				'header_wired'  => $header_wired,
				'footer_wired'  => $footer_wired,
			)
		);
	}

	/**
	 * Append new items to the existing menu WITHOUT rebuilding it.
	 *
	 * Existing blocks pass through serialize_blocks() untouched — every
	 * attribute they carry (className, spectraGSClasses, anything future)
	 * survives byte-for-byte at the attrs level. Only three things happen:
	 *  - a genuinely new item is appended as fresh markup,
	 *  - a duplicate (same page id or url) is skipped,
	 *  - a duplicate carrying children gets those children merged into the
	 *    existing entry in place (union by url).
	 *
	 * @param array<int|string, array<string, mixed>> $blocks        Parsed existing blocks.
	 * @param array<int, array<string, mixed>>        $items         Validated new items.
	 * @param array<string, mixed>|null               $inherit_top   Styling classes for new top-level entries.
	 * @param array<string, mixed>|null               $inherit_child Styling classes for new child entries.
	 * @return array{0: string, 1: int} New post content + final top-level entry count.
	 */
	private function append_items( array $blocks, array $items, ?array $inherit_top, ?array $inherit_child ): array {
		$by_id          = array();
		$by_url         = array();
		$existing_count = 0;
		foreach ( $blocks as $i => $block ) {
			if ( ! $this->is_menu_entry( $block ) ) {
				continue;
			}
			$existing_count++;
			$raw_attrs = $block['attrs'] ?? null;
			$attrs     = is_array( $raw_attrs ) ? $raw_attrs : array();
			$id        = isset( $attrs['id'] ) ? absint( $attrs['id'] ) : 0;
			if ( $id > 0 && ! isset( $by_id[ $id ] ) ) {
				$by_id[ $id ] = $i;
			}
			$url_key = untrailingslashit( (string) ( $attrs['url'] ?? '' ) );
			if ( '' !== $url_key && ! isset( $by_url[ $url_key ] ) ) {
				$by_url[ $url_key ] = $i;
			}
		}

		$appended = array();
		foreach ( $items as $item ) {
			$id      = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$url_key = untrailingslashit( (string) ( $item['url'] ?? '' ) );

			$dedup_index = null;
			if ( $id > 0 && isset( $by_id[ $id ] ) ) {
				$dedup_index = $by_id[ $id ];
			} elseif ( '' !== $url_key && isset( $by_url[ $url_key ] ) ) {
				$dedup_index = $by_url[ $url_key ];
			}

			if ( null === $dedup_index ) {
				$markup = $this->build_entry_markup( $item, $inherit_top, $inherit_child );
				if ( null !== $markup ) {
					$appended[] = $markup;
				}
				continue;
			}

			$raw_children = $item['children'] ?? null;
			$children     = is_array( $raw_children ) ? $raw_children : array();
			if ( ! empty( $children ) ) {
				$this->merge_children_into_block( $blocks[ $dedup_index ], $children, $inherit_child );
			}
		}

		/**
		 * Re-serialize the merged block tree.
		 *
		 * @psalm-suppress ArgumentTypeCoercion -- $blocks retains parse_blocks() shape through the in-place child merges above.
		 */
		$content = trim( serialize_blocks( $blocks ) );
		if ( ! empty( $appended ) ) {
			$joined  = implode( "\n", $appended );
			$content = '' === $content ? $joined : $content . "\n" . $joined;
		}

		return array( $content, $existing_count + count( $appended ) );
	}

	/**
	 * Build the replace-mode menu. The final item list is exactly $items, but
	 * nothing styled is rebuilt when it doesn't have to be:
	 *
	 *  - an item matching an existing entry (by page id, then url) REUSES that
	 *    entry's block — className, spectraGSClasses, every attribute — with
	 *    only its label/url updated from the request,
	 *  - a genuinely new item is minted as fresh markup styled like its siblings,
	 *  - non-menu blocks the navigation carries (search, social icons, spacers)
	 *    are preserved verbatim after the links — "replace" means "set the menu
	 *    items", never "empty the navigation".
	 *
	 * @param array<int|string, array<string, mixed>> $blocks        Parsed existing blocks.
	 * @param array<int, array<string, mixed>>        $items         Validated new items.
	 * @param array<string, mixed>|null               $inherit_top   Styling classes for new top-level entries.
	 * @param array<string, mixed>|null               $inherit_child Styling classes for new child entries.
	 * @return array{0: string, 1: int} New post content + final top-level entry count.
	 */
	private function replace_items( array $blocks, array $items, ?array $inherit_top, ?array $inherit_child ): array {
		$by_id  = array();
		$by_url = array();
		$other  = array();
		foreach ( $blocks as $i => $block ) {
			if ( ! $this->is_menu_entry( $block ) ) {
				// parse_blocks() emits whitespace filler as null-name blocks —
				// not content, drop it. Everything else is preserved.
				if ( null !== ( $block['blockName'] ?? null ) ) {
					$other[] = $block;
				}
				continue;
			}
			$raw_attrs = $block['attrs'] ?? null;
			$attrs     = is_array( $raw_attrs ) ? $raw_attrs : array();
			$id        = isset( $attrs['id'] ) ? absint( $attrs['id'] ) : 0;
			if ( $id > 0 && ! isset( $by_id[ $id ] ) ) {
				$by_id[ $id ] = $i;
			}
			$url_key = untrailingslashit( (string) ( $attrs['url'] ?? '' ) );
			if ( '' !== $url_key && ! isset( $by_url[ $url_key ] ) ) {
				$by_url[ $url_key ] = $i;
			}
		}

		$markup = array();
		foreach ( $items as $item ) {
			$id      = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$url_key = untrailingslashit( (string) ( $item['url'] ?? '' ) );

			$match = null;
			if ( $id > 0 && isset( $by_id[ $id ] ) ) {
				$match = $by_id[ $id ];
			} elseif ( '' !== $url_key && isset( $by_url[ $url_key ] ) ) {
				$match = $by_url[ $url_key ];
			}

			if ( null === $match ) {
				$fresh = $this->build_entry_markup( $item, $inherit_top, $inherit_child );
				if ( null !== $fresh ) {
					$markup[] = $fresh;
				}
				continue;
			}

			$block          = $blocks[ $match ];
			$raw_attrs      = $block['attrs'] ?? null;
			$attrs          = is_array( $raw_attrs ) ? $raw_attrs : array();
			$attrs['label'] = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
			$url            = trim( (string) ( $item['url'] ?? '' ) );
			if ( '' !== $url ) {
				$attrs['url'] = esc_url( $url );
			}
			$block['attrs'] = $attrs;

			$raw_children = $item['children'] ?? null;
			$children     = is_array( $raw_children ) ? $raw_children : array();
			if ( ! empty( $children ) ) {
				$this->merge_children_into_block( $block, $children, $inherit_child );
			}

			/**
			 * Serialize the rebuilt menu item block.
			 *
			 * @psalm-suppress ArgumentTypeCoercion -- $block retains parse_blocks() shape.
			 */
			$markup[] = trim( serialize_blocks( array( $block ) ) );
		}

		$item_count = count( $markup );
		foreach ( $other as $block ) {
			/**
			 * Serialize a preserved non-menu block (search, social, spacer).
			 *
			 * @psalm-suppress ArgumentTypeCoercion -- $block retains parse_blocks() shape.
			 */
			$markup[] = trim( serialize_blocks( array( $block ) ) );
		}

		return array( implode( "\n", $markup ), $item_count );
	}

	/**
	 * Merge new children into an existing menu entry block IN PLACE, keeping
	 * every attribute the entry already carries. A flat navigation-link that
	 * gains children becomes a navigation-submenu (its attrs travel with it).
	 * Union by url — children already present are skipped.
	 *
	 * @param array<string, mixed>      $block         Parsed entry block (by reference).
	 * @param array<int, mixed>         $children      New child items.
	 * @param array<string, mixed>|null $inherit_child Fallback styling classes for new children.
	 */
	private function merge_children_into_block( array &$block, array $children, ?array $inherit_child ): void {
		$raw_inner = $block['innerBlocks'] ?? null;
		$inner     = is_array( $raw_inner ) ? $raw_inner : array();

		// Local siblings beat the menu-wide fallback: children of THIS entry
		// should match the classes THIS entry's children already use.
		$inherit = $this->dominant_link_classes( $inner );
		if ( null === $inherit ) {
			$inherit = $inherit_child;
		}

		$known_urls = array();
		foreach ( $inner as $child ) {
			$child_attrs = is_array( $child['attrs'] ?? null ) ? $child['attrs'] : array();
			$key         = untrailingslashit( (string) ( $child_attrs['url'] ?? '' ) );
			if ( '' !== $key ) {
				$known_urls[ $key ] = true;
			}
		}

		$added = false;
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $child['label'] ?? '' ) );
			$url   = (string) ( $child['url'] ?? '' );
			$key   = untrailingslashit( $url );
			if ( '' === $label || '' === trim( $url ) || isset( $known_urls[ $key ] ) ) {
				continue;
			}

			$attrs = array(
				'label'          => $label,
				'url'            => esc_url( $url ),
				'kind'           => 'custom',
				'isTopLevelLink' => false,
			);
			$attrs = $this->with_inherited_classes( $attrs, $inherit );

			$inner[] = array(
				'blockName'    => 'core/navigation-link',
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			);

			$known_urls[ $key ] = true;
			$added              = true;
		}

		if ( ! $added ) {
			return;
		}

		if ( 'core/navigation-link' === ( $block['blockName'] ?? '' ) ) {
			$block['blockName'] = 'core/navigation-submenu';
			if ( is_array( $block['attrs'] ?? null ) ) {
				// The submenu itself is the top-level entry.
				unset( $block['attrs']['isTopLevelLink'] );
			}
		}

		// Rebuild the inner bookkeeping so serialize_block() interleaves the
		// children correctly: one placeholder per child, newline-separated.
		$inner_content = array();
		foreach ( array_keys( $inner ) as $unused ) {
			$inner_content[] = "\n";
			$inner_content[] = null;
		}
		$inner_content[] = "\n";

		$block['innerBlocks']  = $inner;
		$block['innerHTML']    = '';
		$block['innerContent'] = $inner_content;
	}

	/**
	 * Markup for ONE top-level menu entry — a navigation-link, or a
	 * navigation-submenu when the item carries non-empty children.
	 *
	 * @param array<string, mixed>      $item          Raw item.
	 * @param array<string, mixed>|null $inherit_top   Styling classes for the entry.
	 * @param array<string, mixed>|null $inherit_child Styling classes for its children.
	 * @return string|null Serialized block markup, or null for an invalid item.
	 */
	private function build_entry_markup( array $item, ?array $inherit_top, ?array $inherit_child ): ?string {
		$label = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
		if ( '' === $label ) {
			return null;
		}

		$url   = trim( (string) ( $item['url'] ?? '' ) );
		$attrs = array(
			'label'          => $label,
			'url'            => esc_url( '' === $url ? '#' : $url ),
			'isTopLevelLink' => true,
		);

		$id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		if ( $id > 0 ) {
			$attrs['kind'] = 'post-type';
			$attrs['id']   = $id;
		} else {
			$attrs['kind'] = 'custom';
		}

		$attrs = $this->with_inherited_classes( $attrs, $inherit_top );

		$children     = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
		$inner_blocks = $this->build_submenu_child_blocks( $children, $inherit_child );

		if ( empty( $inner_blocks ) ) {
			return '<!-- wp:navigation-link ' . Helpers::safe_json_encode( $attrs ) . ' /-->';
		}

		// Submenu wrapper carries the same attrs as a navigation-link except
		// `isTopLevelLink` is dropped — the submenu itself is the top-level
		// entry. WP-core's parse_blocks() expects the submenu's inner blocks
		// between its open/close comment markers.
		$submenu_attrs = $attrs;
		unset( $submenu_attrs['isTopLevelLink'] );

		return '<!-- wp:navigation-submenu ' . Helpers::safe_json_encode( $submenu_attrs ) . ' -->' . "\n"
			. implode( "\n", $inner_blocks ) . "\n"
			. '<!-- /wp:navigation-submenu -->';
	}

	/**
	 * Build inner `wp:navigation-link` block markup for the children of a
	 * submenu. Children carry `kind=custom` because the schema only exposes
	 * `label`+`url` for children. Children with an empty / missing label or
	 * url are silently dropped.
	 *
	 * @param array<int, mixed>         $children Raw child items.
	 * @param array<string, mixed>|null $inherit  Styling classes for the children.
	 * @return array<int, string> Serialized block markup, one entry per valid child.
	 */
	private function build_submenu_child_blocks( array $children, ?array $inherit = null ): array {
		$blocks = array();
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $child['label'] ?? '' ) );
			$url   = (string) ( $child['url'] ?? '' );
			if ( '' === $label || '' === trim( $url ) ) {
				continue;
			}

			$attrs = array(
				'label'          => $label,
				'url'            => esc_url( $url ),
				'kind'           => 'custom',
				'isTopLevelLink' => false,
			);
			$attrs = $this->with_inherited_classes( $attrs, $inherit );

			$blocks[] = '<!-- wp:navigation-link ' . Helpers::safe_json_encode( $attrs ) . ' /-->';
		}

		return $blocks;
	}

	/**
	 * The styling classes the existing menu entries share, so a new entry can
	 * be born matching its siblings. In the GBS grammar all per-block styling
	 * lives in `className` (mirrored in `spectraGSClasses`), so inheriting
	 * these two attributes IS inheriting the menu's look.
	 *
	 * Picks the most frequent non-empty className among the given entry
	 * blocks; ties resolve to the first seen. Returns null when no entry
	 * carries one (an unstyled menu stays unstyled).
	 *
	 * @param array<int|string, array<string, mixed>> $blocks Parsed blocks to scan (one level, no recursion).
	 * @return array{className: string, spectraGSClasses: array<array-key, mixed>|null}|null
	 */
	private function dominant_link_classes( array $blocks ): ?array {
		$tally = array();
		$gs    = array();
		foreach ( $blocks as $block ) {
			if ( ! $this->is_menu_entry( $block ) ) {
				continue;
			}
			$raw_attrs  = $block['attrs'] ?? null;
			$attrs      = is_array( $raw_attrs ) ? $raw_attrs : array();
			$class_name = trim( (string) ( $attrs['className'] ?? '' ) );
			if ( '' === $class_name ) {
				continue;
			}
			$tally[ $class_name ] = ( $tally[ $class_name ] ?? 0 ) + 1;
			$gs_value             = $attrs['spectraGSClasses'] ?? null;
			if ( ! isset( $gs[ $class_name ] ) && is_array( $gs_value ) ) {
				$gs[ $class_name ] = $gs_value;
			}
		}

		if ( empty( $tally ) ) {
			return null;
		}

		$winner = array_key_first( $tally );
		foreach ( $tally as $class_name => $count ) {
			if ( $count > $tally[ $winner ] ) {
				$winner = $class_name;
			}
		}

		return array(
			'className'        => $winner,
			'spectraGSClasses' => $gs[ $winner ] ?? null,
		);
	}

	/**
	 * Apply inherited styling classes to a fresh entry's attrs.
	 *
	 * @param array<string, mixed>      $attrs   Entry attrs.
	 * @param array<string, mixed>|null $inherit Result of dominant_link_classes().
	 * @return array<string, mixed>
	 */
	private function with_inherited_classes( array $attrs, ?array $inherit ): array {
		if ( null === $inherit ) {
			return $attrs;
		}
		$class_name = trim( (string) ( $inherit['className'] ?? '' ) );
		if ( '' === $class_name ) {
			return $attrs;
		}
		$attrs['className'] = $class_name;
		$gs_classes         = $inherit['spectraGSClasses'] ?? null;
		if ( is_array( $gs_classes ) ) {
			$attrs['spectraGSClasses'] = $gs_classes;
		}
		return $attrs;
	}

	/**
	 * All navigation-link blocks nested inside submenu entries, flattened —
	 * the sibling pool for styling new CHILD entries.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks Parsed top-level blocks.
	 * @return array<int, array<string, mixed>>
	 */
	private function all_submenu_children( array $blocks ): array {
		$children = array();
		foreach ( $blocks as $block ) {
			if ( 'core/navigation-submenu' !== ( $block['blockName'] ?? '' ) ) {
				continue;
			}
			$raw_inner = $block['innerBlocks'] ?? null;
			$inner     = is_array( $raw_inner ) ? $raw_inner : array();
			foreach ( $inner as $child ) {
				$children[] = $child;
			}
		}
		return $children;
	}

	/**
	 * Whether a parsed block is a top-level menu entry.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return bool
	 */
	private function is_menu_entry( array $block ): bool {
		$name = (string) ( $block['blockName'] ?? '' );
		return 'core/navigation-link' === $name || 'core/navigation-submenu' === $name;
	}

	/**
	 * Create a fresh navigation entity. Fallback only — when the header has no
	 * live menu at all (no entity ref, no inline children). Adopting some other
	 * wp_navigation post here (the old behavior: newest published one) would
	 * rewrite a menu the header never rendered — an invisible write to an
	 * entity nothing on screen accounts for.
	 *
	 * @return int|\WP_Error Navigation post ID.
	 */
	private function create_navigation_post() {
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
	 * The first core/navigation block in a template part, after pattern
	 * expansion — the block that decides which menu that part renders.
	 *
	 * @param string $area Template part slug (header/footer).
	 * @return array<string, mixed>|null Parsed block, or null when the part or block is absent.
	 */
	private function first_navigation_in_part( string $area ): ?array {
		$template = get_block_template( get_stylesheet() . '//' . $area, 'wp_template_part' );
		if ( ! $template ) {
			return null;
		}

		$parsed = $this->expand_pattern_references( parse_blocks( $template->content ) );
		return $this->find_first_navigation( $parsed );
	}

	/**
	 * Depth-first search for the first core/navigation block.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array<string, mixed>|null
	 */
	private function find_first_navigation( array $blocks ): ?array {
		foreach ( $blocks as $block ) {
			if ( 'core/navigation' === ( $block['blockName'] ?? '' ) ) {
				return $block;
			}
			$inner = $block['innerBlocks'] ?? array();
			if ( is_array( $inner ) && ! empty( $inner ) ) {
				$found = $this->find_first_navigation( $inner );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * The wp_navigation post a navigation block references, when that
	 * reference is live.
	 *
	 * @param array<string, mixed>|null $nav Parsed core/navigation block.
	 * @return int|null Post ID, or null when there is no valid reference.
	 */
	private function valid_navigation_ref( ?array $nav ): ?int {
		if ( null === $nav ) {
			return null;
		}
		$ref = isset( $nav['attrs']['ref'] ) ? absint( $nav['attrs']['ref'] ) : 0;
		if ( $ref <= 0 ) {
			return null;
		}
		// Core renders nothing for a non-published menu ref, so anything else
		// is a dangling reference, not a live menu.
		$post = get_post( $ref );
		if ( ! $post || 'wp_navigation' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		return $ref;
	}

	/**
	 * Whether a template part's navigation block may be re-pointed at the
	 * menu entity this ability just updated.
	 *
	 * Never adopt a block that owns a menu of its own: inline children (a
	 * footer's own link column) or a live reference to a DIFFERENT menu
	 * entity. Two cases are fair game: an empty or dangling navigation block
	 * (wiring it gives it real content and destroys nothing), and inline
	 * children whose exact serialized bytes THIS call just captured into the
	 * entity being wired ($owned_markup, the inline-menu migration path) —
	 * clearing those repeats nothing on screen and loses nothing.
	 *
	 * @param array<string, mixed>|null $nav          Parsed core/navigation block.
	 * @param int                       $nav_id       The menu entity being wired.
	 * @param string|null               $owned_markup Serialized inline blocks already migrated into $nav_id.
	 * @return bool
	 */
	private function can_adopt_navigation( ?array $nav, int $nav_id, ?string $owned_markup = null ): bool {
		if ( null === $nav ) {
			return false;
		}
		if ( ! empty( $nav['innerBlocks'] ) ) {
			/**
			 * Serialize the nav's inline children to compare against captured markup.
			 *
			 * @psalm-suppress ArgumentTypeCoercion -- innerBlocks retain parse_blocks() shape.
			 */
			$inline = trim( serialize_blocks( $nav['innerBlocks'] ) );
			if ( null === $owned_markup || $inline !== $owned_markup ) {
				return false;
			}
		}
		$ref = isset( $nav['attrs']['ref'] ) ? absint( $nav['attrs']['ref'] ) : 0;
		if ( $ref > 0 && $ref !== $nav_id ) {
			// A published foreign menu is someone else's — hands off. A
			// trashed/draft/deleted ref renders nothing, so re-pointing it
			// destroys nothing.
			$other = get_post( $ref );
			if ( $other && 'wp_navigation' === $other->post_type && 'publish' === $other->post_status ) {
				return false;
			}
		}
		return true;
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
	 * Only the FIRST navigation block is considered, and only when
	 * {@link can_adopt_navigation} says it owns no menu of its own.
	 *
	 * @param string      $area          Template part slug (header/footer).
	 * @param int         $nav_id        Navigation post ID.
	 * @param array       $default_attrs Attributes to seed a new nav block.
	 * @param string|null $owned_markup  Serialized inline blocks already migrated into $nav_id.
	 * @return bool Whether the template part was updated (or already correct).
	 */
	private function wire_navigation( string $area, int $nav_id, array $default_attrs, ?string $owned_markup = null ): bool {
		$template = get_block_template( get_stylesheet() . '//' . $area, 'wp_template_part' );
		if ( ! $template ) {
			return false;
		}

		$content = $template->content;
		$parsed  = parse_blocks( $content );

		// If the part is just a wp:pattern reference (no DB override yet, or a
		// DB override that was itself written as a pattern ref), expand it so
		// the navigation block inside the pattern is reachable.
		/**
		 * Expand pattern refs so the nested navigation block is reachable.
		 *
		 * @psalm-suppress InvalidScalarArgument -- parse_blocks() shape is compatible with the looser array<int, array<string, mixed>> expected here.
		 */
		$parsed = $this->expand_pattern_references( $parsed );

		if ( $this->navigation_already_wired( $parsed, $nav_id ) ) {
			// Already pointing at this menu — nothing to change. Persisting the
			// re-serialized content here would only rewrite the part cosmetically
			// (serialize_blocks re-encodes attrs, so key order can differ), and
			// core resolves core/pattern refs at render time anyway.
			return true;
		}

		if ( ! $this->can_adopt_navigation( $this->find_first_navigation( $parsed ), $nav_id, $owned_markup ) ) {
			return false;
		}

		if ( ! $this->set_navigation_ref( $parsed, $nav_id, $default_attrs ) ) {
			return false;
		}

		/**
		 * Persist the rewired template part content.
		 *
		 * @psalm-suppress ArgumentTypeCoercion -- $parsed retains parse_blocks() shape through expand_pattern_references.
		 */
		return $this->persist_template_part_content( $template, $area, serialize_blocks( $parsed ), $content );
	}

	/**
	 * Expand any top-level core/pattern block references into their registered
	 * block content. Non-pattern blocks pass through untouched.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks Parsed blocks from parse_blocks().
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

		// wp_update_post()/wp_insert_post() run wp_unslash() on the data, so slash
		// first — otherwise JSON unicode escapes in block-comment attrs (`<`, `&`
		// from inline `<span>`/`&` in chrome copy) lose their backslash and render
		// as literal `u003c`/`u0026`. Slash once here; both write paths reuse it.
		$new_content = wp_slash( $new_content );

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
	 * block found. Returns true when a block was updated. Only ever reached
	 * for a block {@link can_adopt_navigation} already cleared, so clearing
	 * the inner fields destroys nothing.
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
}

Update_Navigation::register();
