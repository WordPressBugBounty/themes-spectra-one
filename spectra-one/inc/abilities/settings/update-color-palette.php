<?php
/**
 * Update Color Palette Ability
 *
 * Updates the FSE global styles color palette and element styles
 * with brand colors from the website builder interview.
 *
 * @package Spectra One
 * @subpackage Abilities
 * @since 1.2.0
 */

declare( strict_types=1 );

namespace Swt\Abilities;

use Swt\Abilities\Traits\Global_Styles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Update_Color_Palette
 */
final class Update_Color_Palette extends Ability {
	use Global_Styles;

	/**
	 * Default background color used in the palette.
	 */
	private const COLOR_BACKGROUND = '#FFFFFF';

	/**
	 * Default black color used in the palette.
	 */
	private const COLOR_BLACK = '#000000';

	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/update-color-palette';
		$this->label       = __( 'Update Spectra One Color Palette', 'spectra-one' );
		$this->description = __( 'Updates the FSE global styles color palette and element styles (text, headings, links, buttons) with brand colors. Accepts 4 hex colors: primary, secondary, heading, body. Derives supporting colors (surface, outline, tertiary) automatically.', 'spectra-one' );
		$this->capability  = 'edit_theme_options';
		// Same input → same end state (merge-by-slug + per-key element
		// writes), so a retry is safe. destructive stays true.
		$this->meta['annotations']['idempotent'] = true;
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
				'colors' => array(
					'type'        => 'array',
					'description' => 'Array of 4 brand color hex values in order: [primary, secondary, heading, body]. Each must be a 6-digit hex like #FF6B6B.',
					'items'       => array(
						'type' => 'string',
					),
				),
			),
			'required'   => array( 'colors' ),
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
				'palette'          => array(
					'type'        => 'array',
					'description' => 'The full color palette that was applied.',
				),
				'global_styles_id' => array(
					'type'        => 'integer',
					'description' => 'The global styles post ID that was updated.',
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
			'update color palette with brand colors',
			'set theme colors to blue and gold',
			'apply brand colors to the website',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$colors = $args['colors'] ?? array();

		$validation_error = $this->validate_colors( $colors );
		if ( null !== $validation_error ) {
			return $validation_error;
		}

		$context = $this->get_global_styles();
		if ( is_wp_error( $context ) ) {
			return Response::from_wp_error( $context );
		}
		if ( null === $context ) {
			return Response::error(
				__( 'No global styles found. Is an FSE/block theme active?', 'spectra-one' ),
				__( 'This ability requires an active block theme with global styles support.', 'spectra-one' )
			);
		}

		$palette       = $this->build_palette( $colors );
		$global_styles = $this->apply_palette_and_elements( $context['styles'], $palette );

		$result = $this->save_global_styles( $context['ID'], $global_styles );
		if ( is_wp_error( $result ) ) {
			return Response::from_wp_error( $result );
		}

		return Response::success(
			__( 'Color palette and element styles updated with brand colors.', 'spectra-one' ),
			array(
				'palette'          => $palette,
				'global_styles_id' => $context['ID'],
			)
		);
	}

	/**
	 * Validate the incoming colors array.
	 *
	 * @param mixed $colors Raw input.
	 * @return array|null Error response or null when valid.
	 */
	private function validate_colors( $colors ): ?array {
		if ( ! is_array( $colors ) || count( $colors ) < 4 ) {
			return Response::error(
				__( 'Exactly 4 brand colors are required: [primary, secondary, heading, body].', 'spectra-one' ),
				__( 'Pass an array of 4 hex color strings like ["#0B3C5D", "#328CC1", "#D9B310", "#86BBD8"].', 'spectra-one' )
			);
		}

		foreach ( $colors as $i => $color ) {
			if ( ! is_string( $color ) || 1 !== preg_match( '/^#[0-9A-Fa-f]{6}$/', $color ) ) {
				$safe_value = is_scalar( $color ) ? sanitize_text_field( (string) $color ) : '';
				return Response::error(
					/* translators: 1: color index, 2: color value */
					sprintf( __( 'Invalid hex color at index %1$d: %2$s', 'spectra-one' ), (int) $i, $safe_value ),
					__( 'Colors must be 6-digit hex values like #FF6B6B.', 'spectra-one' )
				);
			}
		}

		return null;
	}

	/**
	 * Build the full FSE palette with derived supporting colors.
	 *
	 * @param array<int, string> $colors Brand colors [primary, secondary, heading, body].
	 * @return array<int, array{slug: string, color: string, name: string}>
	 */
	private function build_palette( array $colors ): array {
		[ $primary, $secondary, $heading, $body ] = $colors;

		return array(
			array(
				'slug'  => 'primary',
				'color' => $primary,
				'name'  => __( 'Primary', 'spectra-one' ),
			),
			array(
				'slug'  => 'secondary',
				'color' => $secondary,
				'name'  => __( 'Secondary', 'spectra-one' ),
			),
			array(
				'slug'  => 'heading',
				'color' => $heading,
				'name'  => __( 'Heading', 'spectra-one' ),
			),
			array(
				'slug'  => 'body',
				'color' => $body,
				'name'  => __( 'Body', 'spectra-one' ),
			),
			array(
				'slug'  => 'foreground',
				'color' => $primary,
				'name'  => __( 'Foreground', 'spectra-one' ),
			),
			array(
				'slug'  => 'background',
				'color' => self::COLOR_BACKGROUND,
				'name'  => __( 'Background', 'spectra-one' ),
			),
			array(
				'slug'  => 'surface',
				'color' => self::lighten_color( $primary, 0.95 ),
				'name'  => __( 'Surface', 'spectra-one' ),
			),
			array(
				'slug'  => 'tertiary',
				'color' => self::lighten_color( $primary, 0.90 ),
				'name'  => __( 'Tertiary', 'spectra-one' ),
			),
			array(
				'slug'  => 'outline',
				'color' => self::lighten_color( $heading, 0.80 ),
				'name'  => __( 'Outline', 'spectra-one' ),
			),
			array(
				'slug'  => 'black',
				'color' => self::COLOR_BLACK,
				'name'  => __( 'Black', 'spectra-one' ),
			),
		);
	}

	/**
	 * Apply palette and element styles onto a global styles array.
	 *
	 * @param array<string, mixed> $global_styles Existing global styles.
	 * @param array<int, array>    $palette       Palette to set.
	 * @return array<string, mixed> Updated global styles.
	 */
	private function apply_palette_and_elements( array $global_styles, array $palette ): array {
		Helpers::ensure_nested( $global_styles, array( 'settings', 'color', 'palette' ) );
		// Canonical theme.json shape: a FLAT list at settings.color.palette.
		// NOT settings.color.palette.custom — the custom/theme/default origin
		// split exists only in the editor's MERGED settings API, never in the
		// stored user global-styles post. WP's WP_Theme_JSON sanitizer treats a
		// non-list palette node as invalid and drops it, so a nested `custom`
		// write silently registers no presets (Site Editor + frontend unchanged).
		//
		// Merge by slug: this tool owns its role slugs; any other swatch the
		// user added in the Site Editor is theirs and survives.
		$existing                                      = $global_styles['settings']['color']['palette'] ?? null;
		$global_styles['settings']['color']['palette'] = $this->merge_palette_by_slug(
			is_array( $existing ) ? $this->normalize_palette_list( $existing ) : array(),
			$palette
		);

		// Every write below is per-key: assigning a whole node here would
		// delete its siblings — typography a user (or update-typography)
		// set on the same element, button padding, link textDecoration.
		Helpers::ensure_nested( $global_styles, array( 'styles', 'color' ) );
		$global_styles['styles']['color']['text']       = 'var:preset|color|body';
		$global_styles['styles']['color']['background'] = 'var:preset|color|background';

		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'heading', 'color' ) );
		$global_styles['styles']['elements']['heading']['color']['text'] = 'var:preset|color|heading';

		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'link', 'color' ) );
		$global_styles['styles']['elements']['link']['color']['text'] = 'var:preset|color|primary';
		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'link', ':hover', 'color' ) );
		$global_styles['styles']['elements']['link'][':hover']['color']['text'] = 'var:preset|color|secondary';

		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'button', 'color' ) );
		$global_styles['styles']['elements']['button']['color']['text']       = 'var:preset|color|background';
		$global_styles['styles']['elements']['button']['color']['background'] = 'var:preset|color|primary';
		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'button', ':hover', 'color' ) );
		$global_styles['styles']['elements']['button'][':hover']['color']['text']       = 'var:preset|color|background';
		$global_styles['styles']['elements']['button'][':hover']['color']['background'] = 'var:preset|color|secondary';

		return $global_styles;
	}

	/**
	 * Normalize a stored palette node to the canonical FLAT entry list.
	 *
	 * Older writes left an origin-keyed map ({custom: [...], theme: [...]}) at
	 * settings.color.palette — get-color-palette still reads that legacy shape.
	 * Fed raw into the slug merge, each origin bucket has no slug and would
	 * pass through as a nested LIST; WP_Theme_JSON then drops the
	 * non-conforming entries and the user's swatches silently vanish. Flatten
	 * the buckets instead — custom wins over theme on a slug collision, the
	 * same precedence get-color-palette reads with.
	 *
	 * @param array<int|string, mixed> $palette Stored palette node.
	 * @return array<int, mixed> Flat palette entry list.
	 */
	private function normalize_palette_list( array $palette ): array {
		if ( isset( $palette[0] ) || array() === $palette ) {
			// Canonical flat list already (or empty).
			return array_values( $palette );
		}

		$flat = array();
		$seen = array();
		foreach ( array( 'custom', 'theme', 'default' ) as $origin ) {
			$bucket = $palette[ $origin ] ?? null;
			if ( ! is_array( $bucket ) ) {
				continue;
			}
			foreach ( $bucket as $entry ) {
				$slug = is_array( $entry ) ? (string) ( $entry['slug'] ?? '' ) : '';
				if ( '' !== $slug ) {
					if ( isset( $seen[ $slug ] ) ) {
						continue;
					}
					$seen[ $slug ] = true;
				}
				$flat[] = $entry;
			}
		}

		return $flat;
	}

	/**
	 * Merge the tool's palette entries into the existing palette by slug.
	 *
	 * Entries whose slug this tool mints are replaced in place; entries it
	 * doesn't know (user-added swatches) pass through untouched; any of the
	 * tool's slugs not already present are appended in their canonical order.
	 *
	 * @param array<int, mixed>                                            $existing Existing palette entries.
	 * @param array<int, array{slug: string, color: string, name: string}> $ours Palette entries this tool owns.
	 * @return array<int, array> Merged flat palette list.
	 */
	private function merge_palette_by_slug( array $existing, array $ours ): array {
		$ours_by_slug = array();
		foreach ( $ours as $entry ) {
			$ours_by_slug[ $entry['slug'] ] = $entry;
		}

		$merged = array();
		foreach ( $existing as $entry ) {
			$slug = is_array( $entry ) ? (string) ( $entry['slug'] ?? '' ) : '';
			if ( '' !== $slug && isset( $ours_by_slug[ $slug ] ) ) {
				$merged[] = $ours_by_slug[ $slug ];
				unset( $ours_by_slug[ $slug ] );
				continue;
			}
			$merged[] = $entry;
		}

		foreach ( $ours_by_slug as $entry ) {
			$merged[] = $entry;
		}

		return $merged;
	}

	/**
	 * Lighten a hex color by mixing it with white.
	 *
	 * @param string $hex    Hex color like #FF6B6B.
	 * @param float  $amount Amount to lighten (0.0 = original, 1.0 = white).
	 * @return string Lightened hex color.
	 */
	private static function lighten_color( string $hex, float $amount ): string {
		$hex = ltrim( $hex, '#' );
		$r   = hexdec( substr( $hex, 0, 2 ) );
		$g   = hexdec( substr( $hex, 2, 2 ) );
		$b   = hexdec( substr( $hex, 4, 2 ) );

		$r = (int) round( $r + ( 255 - $r ) * $amount );
		$g = (int) round( $g + ( 255 - $g ) * $amount );
		$b = (int) round( $b + ( 255 - $b ) * $amount );

		return sprintf( '#%02X%02X%02X', $r, $g, $b );
	}
}

Update_Color_Palette::register();
