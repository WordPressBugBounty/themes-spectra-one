<?php
/**
 * Install Font Ability
 *
 * Downloads a Google Font and registers it in WordPress Font Library
 * so it becomes available as a preset CSS variable.
 *
 * @package Spectra One
 * @subpackage Abilities
 * @since x.x.x
 */

declare( strict_types=1 );

namespace Swt\Abilities;

use Swt\Abilities\Traits\Global_Styles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Install_Font
 */
final class Install_Font extends Ability {
	use Global_Styles;

	/**
	 * Google Fonts CSS2 API base URL.
	 */
	private const GOOGLE_FONTS_CSS_API = 'https://fonts.googleapis.com/css2';

	/**
	 * Allowed host suffix for downloaded font binaries (defense-in-depth
	 * against SSRF if the upstream CSS response is ever tampered with).
	 */
	private const FONT_HOST_SUFFIX = '.gstatic.com';

	/**
	 * WOFF2 file magic bytes — every valid WOFF2 download starts with these.
	 */
	private const WOFF2_MAGIC = 'wOF2';

	/**
	 * Generic CSS font-family fallback for downloaded Google Fonts. Google
	 * Fonts CSS does not reliably expose the generic category in the CSS2
	 * response, so we use sans-serif as the safest default.
	 */
	private const GENERIC_FALLBACK = 'sans-serif';

	/**
	 * User-Agent used when fetching the Google Fonts CSS2 API. Google serves
	 * woff2 URLs only when the request announces a woff2-capable browser;
	 * the CSS response format therefore depends on UA content negotiation.
	 */
	private const WOFF2_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/install-font';
		$this->label       = __( 'Install Font', 'spectra-one' );
		$this->description = __( 'Downloads a Google Font and registers it in WordPress so blocks can use it. After installing, use spectra-one/update-typography to apply it to body or headings. Provide the font name (e.g. "Playfair Display") and desired weights (e.g. [400, 700]). Skips if the font is already installed.', 'spectra-one' );
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
				'name'    => array(
					'type'        => 'string',
					'description' => 'Google Font family name exactly as shown on fonts.google.com (e.g. "Playfair Display", "DM Sans", "Space Grotesk").',
				),
				'weights' => array(
					'type'        => 'array',
					'description' => 'Font weights to install (e.g. [400, 700]). Defaults to [400, 700] if not provided.',
					'items'       => array(
						'type' => 'integer',
					),
				),
			),
			'required'   => array( 'name' ),
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
				'slug'           => array(
					'type'        => 'string',
					'description' => 'Font slug to use with spectra-one/update-typography.',
				),
				'name'           => array(
					'type'        => 'string',
					'description' => 'Font family name.',
				),
				'weights'        => array(
					'type'        => 'array',
					'description' => 'Installed font weights.',
				),
				'already_exists' => array(
					'type'        => 'boolean',
					'description' => 'Whether the font was already installed.',
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
			'install Playfair Display font',
			'add DM Sans font with weights 400 and 700',
			'install Space Grotesk for headings',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$name = $this->validate_font_name( $args['name'] ?? '' );
		if ( '' === $name ) {
			return Response::error(
				__( 'Font name is required and must contain only letters, numbers, spaces, and hyphens.', 'spectra-one' ),
				__( 'Provide the Google Font name, e.g. "Playfair Display".', 'spectra-one' )
			);
		}

		$raw_weights = $args['weights'] ?? null;
		if ( null !== $raw_weights && ! $this->has_any_valid_weight( $raw_weights ) ) {
			// Caller supplied weights but every one was invalid. Reject explicitly
			// so the caller sees their input was dropped instead of silently
			// falling back to [400, 700].
			return Response::error(
				__( 'All provided weights are invalid.', 'spectra-one' ),
				__( 'Font weights must be integers between 100 and 900 in steps of 100 (e.g. 400, 700).', 'spectra-one' )
			);
		}

		$weights = $this->normalize_weights( $raw_weights ?? array( 400, 700 ) );
		$slug    = sanitize_title( $name );

		if ( $this->font_exists( $slug ) ) {
			return Response::success(
				/* translators: %s: font name */
				sprintf( __( 'Font "%s" is already installed.', 'spectra-one' ), $name ),
				array(
					'slug'           => $slug,
					'name'           => $name,
					'weights'        => $weights,
					'already_exists' => true,
				)
			);
		}

		$font_faces = $this->download_google_font( $name, $slug, $weights );
		if ( empty( $font_faces ) ) {
			return Response::error(
				/* translators: %s: font name */
				sprintf( __( 'Failed to download font "%s" from Google Fonts.', 'spectra-one' ), $name ),
				__( 'Check that the font name is correct and matches fonts.google.com exactly.', 'spectra-one' )
			);
		}

		$font_family_id = $this->create_font_family_post( $name, $slug );
		if ( is_wp_error( $font_family_id ) ) {
			return Response::from_wp_error( $font_family_id );
		}

		$installed_weights = $this->persist_font_face_posts( $font_family_id, $font_faces );
		sort( $installed_weights );
		$this->add_to_global_styles( $name, $slug, $font_faces );

		return Response::success(
			/* translators: 1: font name, 2: comma-separated weights, 3: font slug */
			sprintf( __( 'Font "%1$s" installed with weights: %2$s. Use slug "%3$s" with spectra-one/update-typography.', 'spectra-one' ), $name, implode( ', ', $installed_weights ), $slug ),
			array(
				'slug'           => $slug,
				'name'           => $name,
				'weights'        => $installed_weights,
				'already_exists' => false,
			)
		);
	}

	/**
	 * Whether any entry in the raw weights input normalizes to a valid weight.
	 *
	 * Used to distinguish "caller omitted weights" (use defaults) from "caller
	 * supplied weights but all were invalid" (reject).
	 *
	 * @param mixed $raw Raw weight input.
	 * @return bool
	 */
	private function has_any_valid_weight( $raw ): bool {
		foreach ( (array) $raw as $value ) {
			$num = absint( $value );
			if ( $num >= 100 && $num <= 900 && 0 === $num % 100 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize and validate a Google Fonts family name.
	 *
	 * @param mixed $raw Raw input value.
	 * @return string Sanitized name, or empty string when invalid.
	 */
	private function validate_font_name( $raw ): string {
		if ( ! is_string( $raw ) ) {
			return '';
		}

		$name = sanitize_text_field( trim( $raw ) );
		if ( '' === $name ) {
			return '';
		}

		// Allowlist: letters, digits, spaces, hyphens — matches all known
		// Google Fonts family names and prevents injection into CSS, URLs,
		// and stored JSON.
		if ( 1 !== preg_match( '/^[A-Za-z0-9 \-]+$/', $name ) ) {
			return '';
		}

		return $name;
	}

	/**
	 * Normalize and validate weight inputs (100..900 in steps of 100).
	 *
	 * @param mixed $raw Raw weight input.
	 * @return array<int, int> Valid weights, falling back to [400, 700] when empty.
	 */
	private function normalize_weights( $raw ): array {
		$weights = array_filter(
			array_map( 'absint', (array) $raw ),
			static function ( int $w ): bool {
				return $w >= 100 && $w <= 900 && 0 === $w % 100;
			}
		);

		if ( empty( $weights ) ) {
			return array( 400, 700 );
		}

		return array_values( $weights );
	}

	/**
	 * Persist font-face child posts and return the list of successful weights.
	 *
	 * @param int                                                                                       $family_id Parent font-family post ID.
	 * @param array<int, array{fontFamily: string, fontWeight: string, fontStyle: string, src: string}> $faces     Font face data.
	 * @return array<int, int> Installed weight numbers.
	 */
	private function persist_font_face_posts( int $family_id, array $faces ): array {
		$weights = array();
		foreach ( $faces as $face ) {
			$face_id = $this->create_font_face_post( $family_id, $face );
			if ( ! is_wp_error( $face_id ) ) {
				$weights[] = (int) $face['fontWeight'];
			}
		}

		return $weights;
	}

	/**
	 * Check if a font is already installed (in Font Library or theme).
	 *
	 * @param string $slug Font slug.
	 * @return bool
	 */
	private function font_exists( string $slug ): bool {
		$existing = get_posts(
			array(
				'post_type'   => 'wp_font_family',
				'name'        => $slug,
				'post_status' => array( 'publish', 'draft' ),
				'numberposts' => 1,
			)
		);

		if ( ! empty( $existing ) ) {
			return true;
		}

		$theme_json = \Swt\get_theme_json();
		$families   = $theme_json['settings']['typography']['fontFamilies'] ?? array();
		if ( is_array( $families ) && $this->families_include_slug( $families, $slug ) ) {
			return true;
		}

		$db_styles   = \Swt\get_theme_custom_styles();
		$db_families = $db_styles['post_content']['settings']['typography']['fontFamilies'] ?? array();
		if ( is_array( $db_families ) && $this->families_include_slug( $db_families, $slug ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a font families structure contains the given slug.
	 *
	 * Handles both flat lists and origin-keyed shapes:
	 *   - fontFamilies: [ { slug: ... }, ... ]
	 *   - fontFamilies: { theme: [...], custom: [...] }
	 *
	 * @param array<string, mixed>|array<int, mixed> $families Raw families.
	 * @param string                                 $slug     Slug to match.
	 * @return bool
	 */
	private function families_include_slug( array $families, string $slug ): bool {
		foreach ( $families as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			if ( isset( $entry['slug'] ) && $entry['slug'] === $slug ) {
				return true;
			}

			// Origin-keyed shape: entry is itself a list of font definitions.
			if ( array_values( $entry ) === $entry && $this->families_include_slug( $entry, $slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Download font files from Google Fonts CSS2 API.
	 *
	 * @param string          $name    Font family name.
	 * @param string          $slug    Font slug (sanitized name).
	 * @param array<int, int> $weights Weights to download.
	 * @return array<int, array{fontFamily: string, fontWeight: string, fontStyle: string, src: string}>
	 */
	private function download_google_font( string $name, string $slug, array $weights ): array {
		$url = add_query_arg(
			array(
				'family'  => $name . ':wght@' . implode( ';', array_map( 'strval', $weights ) ),
				'display' => 'swap',
			),
			self::GOOGLE_FONTS_CSS_API
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => self::WOFF2_USER_AGENT,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$css = wp_remote_retrieve_body( $response );

		return $this->parse_and_download_faces( $css, $name, $slug );
	}

	/**
	 * Parse @font-face blocks from Google Fonts CSS and download the files.
	 *
	 * Google Fonts serves multiple @font-face declarations per weight — one
	 * for each unicode-range subset (latin, latin-ext, cyrillic, etc.). The
	 * latin subset is emitted last; we iterate in reverse so that the final
	 * stored file covers basic Latin glyphs.
	 *
	 * @param string $css  Google Fonts CSS response.
	 * @param string $name Font family name.
	 * @param string $slug Font slug.
	 * @return array<int, array{fontFamily: string, fontWeight: string, fontStyle: string, src: string}>
	 */
	private function parse_and_download_faces( string $css, string $name, string $slug ): array {
		$count = preg_match_all( '/@font-face\s*\{([^}]+)\}/s', $css, $blocks );
		if ( false === $count || 0 === $count ) {
			return array();
		}

		$font_dir = wp_get_font_dir();
		$dir_path = isset( $font_dir['path'] ) ? (string) $font_dir['path'] : '';
		$dir_url  = isset( $font_dir['url'] ) ? (string) $font_dir['url'] : '';

		if ( '' === $dir_path || '' === $dir_url ) {
			return array();
		}

		if ( ! wp_mkdir_p( $dir_path ) ) {
			return array();
		}

		$faces = array();
		$seen  = array();

		// Reverse so the last (latin) subset wins per weight+style key.
		foreach ( array_reverse( $blocks[1] ) as $block ) {
			$face = $this->build_face_from_block( $block, $name, $slug, $dir_path, $dir_url, $seen );
			if ( null !== $face ) {
				$faces[] = $face;
			}
		}

		return $faces;
	}

	/**
	 * Parse a single @font-face block, download its binary, and return the
	 * face descriptor. Returns null when the block should be skipped.
	 *
	 * @param string              $block    CSS inside @font-face { ... }.
	 * @param string              $name     Font family name.
	 * @param string              $slug     Font slug.
	 * @param string              $dir_path Font directory path.
	 * @param string              $dir_url  Font directory URL.
	 * @param array<string, bool> $seen     Weight+style keys already processed (by reference).
	 * @return array{fontFamily: string, fontWeight: string, fontStyle: string, src: string}|null
	 */
	private function build_face_from_block( string $block, string $name, string $slug, string $dir_path, string $dir_url, array &$seen ): ?array {
		$weight = 1 === preg_match( '/font-weight:\s*(\d+)/', $block, $m1 ) ? $m1[1] : '400';
		$style  = 1 === preg_match( '/font-style:\s*(\w+)/', $block, $m2 ) ? $m2[1] : 'normal';

		$key = $weight . '-' . $style;
		if ( isset( $seen[ $key ] ) ) {
			return null;
		}

		if ( 1 !== preg_match( '/url\(([^)]+\.woff2)\)/', $block, $m3 ) ) {
			return null;
		}

		$remote_url = trim( $m3[1], '\'" ' );
		if ( ! $this->is_allowed_font_host( $remote_url ) ) {
			return null;
		}

		$filename   = $slug . '-' . $weight . '-' . $style . '.woff2';
		$local_path = $dir_path . '/' . $filename;
		$local_url  = $dir_url . '/' . $filename;

		if ( ! file_exists( $local_path ) && ! $this->download_font_file( $remote_url, $local_path ) ) {
			return null;
		}

		$seen[ $key ] = true;

		return array(
			'fontFamily' => "'" . $name . "'",
			'fontWeight' => $weight,
			'fontStyle'  => $style,
			'src'        => $local_url,
		);
	}

	/**
	 * Ensure the URL points to *.gstatic.com over https.
	 *
	 * @param string $url Remote URL.
	 * @return bool
	 */
	private function is_allowed_font_host( string $url ): bool {
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );

		if ( 'https' !== $scheme || ! is_string( $host ) || '' === $host ) {
			return false;
		}

		$suffix_len = strlen( self::FONT_HOST_SUFFIX );
		return substr( $host, -$suffix_len ) === self::FONT_HOST_SUFFIX;
	}

	/**
	 * Download a font binary, validate magic bytes, and write via WP_Filesystem.
	 *
	 * @param string $remote_url Remote URL.
	 * @param string $local_path Destination path.
	 * @return bool
	 */
	private function download_font_file( string $remote_url, string $local_path ): bool {
		$download = wp_remote_get( $remote_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $download ) || 200 !== wp_remote_retrieve_response_code( $download ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $download );
		if ( '' === $body || self::WOFF2_MAGIC !== substr( $body, 0, 4 ) ) {
			return false;
		}

		return $this->write_font_file( $local_path, $body );
	}

	/**
	 * Write binary content via WP_Filesystem, which is the
	 * wordpress.org-compliant way to persist arbitrary files.
	 *
	 * @param string $path Destination path.
	 * @param string $body File contents.
	 * @return bool
	 */
	private function write_font_file( string $path, string $body ): bool {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			/** @psalm-suppress MissingFile -- File is provided by WordPress at runtime. */
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return false;
		}

		return (bool) $wp_filesystem->put_contents( $path, $body, FS_CHMOD_FILE );
	}

	/**
	 * Create a wp_font_family post.
	 *
	 * @param string $name Font name.
	 * @param string $slug Font slug.
	 * @return int|\WP_Error Post ID or error.
	 */
	private function create_font_family_post( string $name, string $slug ) {
		$content = wp_json_encode(
			array(
				'fontFamily' => "'" . $name . "', " . self::GENERIC_FALLBACK,
			)
		);

		if ( false === $content ) {
			return new \WP_Error( 'swt_font_family_json_failed', __( 'Failed to encode font family data.', 'spectra-one' ) );
		}

		return wp_insert_post(
			array(
				'post_type'    => 'wp_font_family',
				'post_title'   => $name,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => $content,
			),
			true
		);
	}

	/**
	 * Create a wp_font_face child post.
	 *
	 * @param int                                                                           $parent_id Font family post ID.
	 * @param array{fontFamily: string, fontWeight: string, fontStyle: string, src: string} $face      Font face data.
	 * @return int|\WP_Error Post ID or error.
	 */
	private function create_font_face_post( int $parent_id, array $face ) {
		$content = wp_json_encode( $face );
		if ( false === $content ) {
			return new \WP_Error( 'swt_font_face_json_failed', __( 'Failed to encode font face data.', 'spectra-one' ) );
		}

		return wp_insert_post(
			array(
				'post_type'    => 'wp_font_face',
				'post_parent'  => $parent_id,
				'post_title'   => '',
				'post_name'    => '',
				'post_status'  => 'publish',
				'post_content' => $content,
			),
			true
		);
	}

	/**
	 * Add font to global styles so it appears in the font picker
	 * and can be referenced via var:preset|font-family|{slug}.
	 *
	 * @param string                                                                                    $name  Font name.
	 * @param string                                                                                    $slug  Font slug.
	 * @param array<int, array{fontFamily: string, fontWeight: string, fontStyle: string, src: string}> $faces Font face data.
	 * @return bool Whether global styles were updated.
	 */
	private function add_to_global_styles( string $name, string $slug, array $faces ): bool {
		$context = $this->get_global_styles();
		if ( null === $context ) {
			return false;
		}

		$styles = $context['styles'];
		Helpers::ensure_nested( $styles, array( 'settings', 'typography', 'fontFamilies', 'custom' ) );

		$custom = isset( $styles['settings']['typography']['fontFamilies']['custom'] )
			&& is_array( $styles['settings']['typography']['fontFamilies']['custom'] )
			? $styles['settings']['typography']['fontFamilies']['custom']
			: array();

		foreach ( $custom as $existing ) {
			if ( is_array( $existing ) && ( $existing['slug'] ?? '' ) === $slug ) {
				return false;
			}
		}

		$custom[] = array(
			'name'       => $name,
			'slug'       => $slug,
			'fontFamily' => "'" . $name . "', " . self::GENERIC_FALLBACK,
			'fontFace'   => $faces,
		);

		$styles['settings']['typography']['fontFamilies']['custom'] = $custom;

		$result = $this->save_global_styles( $context['ID'], $styles );
		return ! is_wp_error( $result );
	}
}

Install_Font::register();
