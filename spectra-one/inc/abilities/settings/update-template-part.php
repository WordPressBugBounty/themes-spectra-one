<?php
/**
 * Update Template Part Ability
 *
 * Switches the active header or footer pattern by updating
 * the corresponding FSE template part content.
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
 * Class Update_Template_Part
 */
final class Update_Template_Part extends Ability {
	/**
	 * Valid template part areas and their allowed pattern prefixes.
	 */
	private const ALLOWED_AREAS = array(
		'header' => 'spectra-one/header',
		'footer' => 'spectra-one/footer',
	);

	/**
	 * Header pattern slugs whose rendered markup uses a solid background,
	 * so the site title text should be white rather than the heading colour.
	 */
	private const SOLID_HEADER_PATTERNS = array(
		'spectra-one/header',
		'spectra-one/header-2',
		'spectra-one/header-3',
	);

	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/update-template-part';
		$this->label       = __( 'Update Template Part', 'spectra-one' );
		$this->description = __( 'Switches the active header or footer design by updating the FSE template part to reference a different Spectra One pattern. Use spectra-one/list-patterns with category "header" or "footer" to see available patterns first.', 'spectra-one' );
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
				'area'           => array(
					'type'        => 'string',
					'enum'        => array( 'header', 'footer' ),
					'description' => 'The template part area to update.',
				),
				'pattern'        => array(
					'type'        => 'string',
					'description' => 'The pattern slug to activate (e.g. "spectra-one/header-2", "spectra-one/footer-4").',
				),
				'use_site_title' => array(
					'type'        => 'boolean',
					'description' => 'Replace the logo image with a wp:site-title block. Useful for sites without a logo.',
				),
			),
			'required'   => array( 'area', 'pattern' ),
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
				'area'             => array(
					'type'        => 'string',
					'description' => 'The template part area that was updated.',
				),
				'pattern'          => array(
					'type'        => 'string',
					'description' => 'The pattern slug now active.',
				),
				'previous_pattern' => array(
					'type'        => 'string',
					'description' => 'The pattern slug that was previously active.',
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
			'switch header to centered menu layout',
			'use transparent/blend header',
			'change footer to minimal design',
			'set header pattern to spectra-one/header-4',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$area    = sanitize_text_field( $args['area'] ?? '' );
		$pattern = sanitize_text_field( $args['pattern'] ?? '' );

		$validation_error = $this->validate_area_and_pattern( $area, $pattern );
		if ( null !== $validation_error ) {
			return $validation_error;
		}

		$template_part = get_block_template( get_stylesheet() . '//' . $area, 'wp_template_part' );
		if ( ! $template_part ) {
			return Response::error(
				/* translators: %s: area name */
				sprintf( __( 'Could not find the %s template part.', 'spectra-one' ), $area )
			);
		}

		$previous_pattern = $this->extract_current_pattern_slug( $template_part->content );
		$use_site_title   = ! empty( $args['use_site_title'] );
		$new_content      = $use_site_title
			? $this->render_pattern_with_site_title( $pattern )
			: $this->build_pattern_reference( $pattern );

		$result = $this->persist_template_part( $template_part, $area, $new_content );
		if ( is_wp_error( $result ) ) {
			return Response::error( $result->get_error_message() );
		}

		return Response::success(
			/* translators: 1: area name, 2: pattern slug */
			sprintf( __( '%1$s updated to use pattern "%2$s".', 'spectra-one' ), ucfirst( $area ), $pattern ),
			array(
				'area'             => $area,
				'pattern'          => $pattern,
				'previous_pattern' => $previous_pattern,
			)
		);
	}

	/**
	 * Validate the area / pattern pair.
	 *
	 * @param string $area    Area slug.
	 * @param string $pattern Pattern slug.
	 * @return array|null Error response or null when valid.
	 */
	private function validate_area_and_pattern( string $area, string $pattern ): ?array {
		if ( ! isset( self::ALLOWED_AREAS[ $area ] ) ) {
			return Response::error(
				__( 'Invalid area. Must be "header" or "footer".', 'spectra-one' )
			);
		}

		$prefix = self::ALLOWED_AREAS[ $area ];
		if ( 0 !== strpos( $pattern, $prefix ) ) {
			return Response::error(
				/* translators: 1: area name, 2: expected prefix */
				sprintf(
					__( 'Pattern must start with "%1$s" for the %2$s area.', 'spectra-one' ),
					$prefix,
					$area
				)
			);
		}

		$registry = \WP_Block_Patterns_Registry::get_instance();
		if ( ! $registry->is_registered( $pattern ) ) {
			return Response::error(
				/* translators: %s: pattern slug */
				sprintf( __( 'Pattern "%s" is not registered.', 'spectra-one' ), $pattern )
			);
		}

		return null;
	}

	/**
	 * Extract the current pattern slug from template part content.
	 *
	 * @param string $content Template part content.
	 * @return string Current pattern slug or empty string.
	 */
	private function extract_current_pattern_slug( string $content ): string {
		if ( 1 === preg_match( '/<!-- wp:pattern \{"slug":"([^"]+)"\}/', $content, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Build a block reference to the given pattern slug.
	 *
	 * @param string $pattern Pattern slug.
	 * @return string Block markup.
	 */
	private function build_pattern_reference( string $pattern ): string {
		return '<!-- wp:pattern ' . Helpers::safe_json_encode( array( 'slug' => $pattern ) ) . ' /-->';
	}

	/**
	 * Persist the template part content, creating a DB override when needed.
	 *
	 * @param \WP_Block_Template|object $template_part Block template part object with wp_id/content.
	 * @param string                    $area          Area slug.
	 * @param string                    $new_content   New block markup.
	 * @return int|\WP_Error Post ID or error.
	 */
	private function persist_template_part( $template_part, string $area, string $new_content ) {
		if ( ! empty( $template_part->wp_id ) ) {
			return wp_update_post(
				array(
					'ID'           => (int) $template_part->wp_id,
					'post_content' => $new_content,
				),
				true
			);
		}

		$result = wp_insert_post(
			array(
				'post_type'    => 'wp_template_part',
				'post_name'    => $area,
				'post_title'   => ucfirst( $area ),
				'post_status'  => 'publish',
				'post_content' => $new_content,
			),
			true
		);

		if ( ! is_wp_error( $result ) ) {
			wp_set_object_terms( $result, get_stylesheet(), 'wp_theme' );
			wp_set_object_terms( $result, $area, 'wp_template_part_area' );
		}

		return $result;
	}

	/**
	 * Render a pattern and replace the logo image with a wp:site-title block.
	 *
	 * @param string $pattern_slug Pattern slug.
	 * @return string Block markup with site-title instead of logo image.
	 */
	private function render_pattern_with_site_title( string $pattern_slug ): string {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$pattern  = $registry->get_registered( $pattern_slug );

		if ( ! $pattern || empty( $pattern['content'] ) ) {
			return $this->build_pattern_reference( $pattern_slug );
		}

		$content          = (string) $pattern['content'];
		$text_color_slug  = in_array( $pattern_slug, self::SOLID_HEADER_PATTERNS, true ) ? 'white' : 'heading';
		$site_title_block = '<!-- wp:site-title ' . Helpers::safe_json_encode(
			array(
				'style'     => array(
					'typography' => array(
						'fontStyle'  => 'normal',
						'fontWeight' => '700',
						'fontSize'   => '1.25rem',
					),
				),
				'textColor' => $text_color_slug,
				'isLink'    => true,
			)
		) . ' /-->';

		// Replace the first wp:image logo block (marker: site-logo-img class).
		// [^>]* avoids matching across comment boundaries; .*? is scoped by the closing tag.
		$logo_pattern = '/<!-- wp:image [^>]*site-logo-img[^>]*-->.*?<!-- \/wp:image -->/s';
		$replaced     = preg_replace( $logo_pattern, $site_title_block, $content, 1 );

		return is_string( $replaced ) ? $replaced : $content;
	}
}

Update_Template_Part::register();
