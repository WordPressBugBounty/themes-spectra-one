<?php
/**
 * Update Typography Ability
 *
 * Updates the FSE global styles typography settings for body text
 * and headings (font family, weight, line height).
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
 * Class Update_Typography
 */
final class Update_Typography extends Ability {
	use Global_Styles;

	/**
	 * Named font weights supported by theme.json custom.fontWeight values.
	 */
	private const NAMED_WEIGHTS = array(
		'thin',
		'extraLight',
		'light',
		'regular',
		'medium',
		'semiBold',
		'bold',
		'extraBold',
		'black',
	);

	/**
	 * Named line heights supported by theme.json custom.lineHeight values.
	 */
	private const NAMED_LINE_HEIGHTS = array(
		'initial',
		'xxSmall',
		'xSmall',
		'small',
		'medium',
		'large',
	);

	/**
	 * Configure the ability.
	 */
	public function configure(): void {
		$this->id          = 'spectra-one/update-typography';
		$this->label       = __( 'Update Spectra One Typography', 'spectra-one' );
		$this->description = __( 'Updates the FSE global styles typography for body text and headings. Set font family (by slug), font weight, and line height. Font families must be registered in theme.json. Available weights: thin, extraLight, light, regular, medium, semiBold, bold, extraBold, black (or numeric 100-900). Available line heights: initial, xxSmall, xSmall, small, medium, large.', 'spectra-one' );
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
				'body_font_family'    => array(
					'type'        => 'string',
					'description' => 'Font family slug for body text (e.g. "inter"). Must be registered in theme.json or a Google Font slug.',
				),
				'body_font_weight'    => array(
					'type'        => 'string',
					'description' => 'Font weight for body text. Named: thin, extraLight, light, regular, medium, semiBold, bold, extraBold, black. Or numeric: 100-900.',
				),
				'body_line_height'    => array(
					'type'        => 'string',
					'description' => 'Line height for body text. Preset names: initial, xxSmall, xSmall, small, medium, large. Or a numeric value like "1.5".',
				),
				'body_font_size'      => array(
					'type'        => 'string',
					'description' => 'Font size preset slug for body text: x-small, small, medium, large, x-large, xx-large, xxx-large, xxxx-large.',
				),
				'heading_font_family' => array(
					'type'        => 'string',
					'description' => 'Font family slug for all headings (e.g. "inter", "playfair-display"). Must be registered in theme.json or a Google Font slug.',
				),
				'heading_font_weight' => array(
					'type'        => 'string',
					'description' => 'Font weight for headings. Named or numeric (see body_font_weight).',
				),
				'heading_line_height' => array(
					'type'        => 'string',
					'description' => 'Line height for headings. Preset name or numeric value.',
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
					'description' => 'List of typography fields that were updated.',
				),
				'typography'     => array(
					'type'        => 'object',
					'description' => 'Current typography settings after update.',
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
			'set body font to Inter and headings to Playfair Display',
			'update heading font weight to bold',
			'change body font size to large',
			'set typography for the website',
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function execute( $args ) {
		$context = $this->get_global_styles();
		if ( null === $context ) {
			return Response::error(
				__( 'No global styles found. Is an FSE/block theme active?', 'spectra-one' ),
				__( 'This ability requires an active block theme with global styles support.', 'spectra-one' )
			);
		}

		$provided_fields = $this->get_provided_fields( $args );
		if ( empty( $provided_fields ) ) {
			return Response::error(
				__( 'No typography fields provided to update.', 'spectra-one' ),
				__( 'Provide at least one of: body_font_family, body_font_weight, body_line_height, body_font_size, heading_font_family, heading_font_weight, heading_line_height.', 'spectra-one' )
			);
		}

		$global_styles = $context['styles'];
		Helpers::ensure_nested( $global_styles, array( 'styles', 'typography' ) );
		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'heading', 'typography' ) );

		$rejected       = array();
		$updated_fields = array_merge(
			$this->apply_body_typography( $global_styles, $args, $rejected ),
			$this->apply_heading_typography( $global_styles, $args, $rejected )
		);

		if ( ! empty( $rejected ) ) {
			$parts = array();
			foreach ( $rejected as $field => $value ) {
				$parts[] = $field . '=' . $value;
			}
			return Response::error(
				/* translators: %s: comma-separated list of field=value pairs that were rejected */
				sprintf( __( 'Invalid typography values: %s.', 'spectra-one' ), implode( ', ', $parts ) ),
				__( 'Font weights must be named (thin..black) or numeric (100-900 in steps of 100). Line heights must be named (initial, xxSmall..large) or numeric (0 < n <= 5).', 'spectra-one' )
			);
		}

		$result = $this->save_global_styles( $context['ID'], $global_styles );
		if ( is_wp_error( $result ) ) {
			return Response::from_wp_error( $result );
		}

		$typography = array();
		if ( isset( $global_styles['styles']['typography'] ) && is_array( $global_styles['styles']['typography'] ) ) {
			$typography['body'] = $global_styles['styles']['typography'];
		} else {
			$typography['body'] = array();
		}
		if ( isset( $global_styles['styles']['elements']['heading']['typography'] ) && is_array( $global_styles['styles']['elements']['heading']['typography'] ) ) {
			$typography['headings'] = $global_styles['styles']['elements']['heading']['typography'];
		} else {
			$typography['headings'] = array();
		}

		return Response::success(
			/* translators: %s: comma-separated list of updated fields */
			sprintf( __( 'Typography updated: %s.', 'spectra-one' ), implode( ', ', $updated_fields ) ),
			array(
				'updated_fields' => $updated_fields,
				'typography'     => $typography,
			)
		);
	}

	/**
	 * List the typography fields the caller supplied (regardless of validity).
	 *
	 * @param array<string, mixed> $args Input arguments.
	 * @return array<int, string> Field names present in $args with a non-empty value.
	 */
	private function get_provided_fields( array $args ): array {
		$fields = array(
			'body_font_family',
			'body_font_weight',
			'body_line_height',
			'body_font_size',
			'heading_font_family',
			'heading_font_weight',
			'heading_line_height',
		);

		$provided = array();
		foreach ( $fields as $field ) {
			if ( ! empty( $args[ $field ] ) ) {
				$provided[] = $field;
			}
		}

		return $provided;
	}

	/**
	 * Apply body typography fields.
	 *
	 * @param array<string, mixed>  $global_styles Mutated in place.
	 * @param array<string, mixed>  $args          Input arguments.
	 * @param array<string, string> $rejected      Map of field => bad value (by reference).
	 * @return array<int, string> Updated field names.
	 */
	private function apply_body_typography( array &$global_styles, array $args, array &$rejected ): array {
		$updated = array();
		Helpers::ensure_nested( $global_styles, array( 'styles', 'typography' ) );

		if ( ! empty( $args['body_font_family'] ) ) {
			$slug = sanitize_title( (string) $args['body_font_family'] );
			$global_styles['styles']['typography']['fontFamily'] = 'var:preset|font-family|' . $slug;
			$updated[] = 'body_font_family';
		}

		if ( ! empty( $args['body_font_weight'] ) ) {
			$raw    = (string) $args['body_font_weight'];
			$weight = $this->resolve_font_weight( $raw );
			if ( null !== $weight ) {
				$global_styles['styles']['typography']['fontWeight'] = $weight;
				$updated[] = 'body_font_weight';
			} else {
				$rejected['body_font_weight'] = $raw;
			}
		}

		if ( ! empty( $args['body_line_height'] ) ) {
			$raw         = (string) $args['body_line_height'];
			$line_height = $this->resolve_line_height( $raw );
			if ( null !== $line_height ) {
				$global_styles['styles']['typography']['lineHeight'] = $line_height;
				$updated[] = 'body_line_height';
			} else {
				$rejected['body_line_height'] = $raw;
			}
		}

		if ( ! empty( $args['body_font_size'] ) ) {
			$slug = sanitize_title( (string) $args['body_font_size'] );
			$global_styles['styles']['typography']['fontSize'] = 'var:preset|font-size|' . $slug;
			$updated[] = 'body_font_size';
		}

		return $updated;
	}

	/**
	 * Apply heading typography fields.
	 *
	 * @param array<string, mixed>  $global_styles Mutated in place.
	 * @param array<string, mixed>  $args          Input arguments.
	 * @param array<string, string> $rejected      Map of field => bad value (by reference).
	 * @return array<int, string> Updated field names.
	 */
	private function apply_heading_typography( array &$global_styles, array $args, array &$rejected ): array {
		$updated = array();
		Helpers::ensure_nested( $global_styles, array( 'styles', 'elements', 'heading', 'typography' ) );

		/** @psalm-suppress PossiblyUndefinedStringArrayOffset -- ensure_nested seeds the path. */
		$path = &$global_styles['styles']['elements']['heading']['typography'];

		if ( ! empty( $args['heading_font_family'] ) ) {
			$slug               = sanitize_title( (string) $args['heading_font_family'] );
			$path['fontFamily'] = 'var:preset|font-family|' . $slug;
			$updated[]          = 'heading_font_family';
		}

		if ( ! empty( $args['heading_font_weight'] ) ) {
			$raw    = (string) $args['heading_font_weight'];
			$weight = $this->resolve_font_weight( $raw );
			if ( null !== $weight ) {
				$path['fontWeight'] = $weight;
				$updated[]          = 'heading_font_weight';
			} else {
				$rejected['heading_font_weight'] = $raw;
			}
		}

		if ( ! empty( $args['heading_line_height'] ) ) {
			$raw         = (string) $args['heading_line_height'];
			$line_height = $this->resolve_line_height( $raw );
			if ( null !== $line_height ) {
				$path['lineHeight'] = $line_height;
				$updated[]          = 'heading_line_height';
			} else {
				$rejected['heading_line_height'] = $raw;
			}
		}

		return $updated;
	}

	/**
	 * Resolve a font weight input to the FSE var format or raw value.
	 *
	 * Accepts named weights (regular, semiBold) or numeric (400, 600).
	 *
	 * @param string $input Weight name or number.
	 * @return string|null Resolved value or null if invalid.
	 */
	private function resolve_font_weight( string $input ): ?string {
		if ( in_array( $input, self::NAMED_WEIGHTS, true ) ) {
			return 'var:custom|font-weight|' . $input;
		}

		if ( is_numeric( $input ) ) {
			$num = (int) $input;
			if ( $num >= 100 && $num <= 900 && 0 === $num % 100 ) {
				return (string) $num;
			}
		}

		return null;
	}

	/**
	 * Resolve a line height input to the FSE var format or raw value.
	 *
	 * Accepts preset names (xxSmall, medium) or numeric values (1.5).
	 *
	 * @param string $input Line height preset name or number.
	 * @return string|null Resolved value or null if invalid.
	 */
	private function resolve_line_height( string $input ): ?string {
		if ( in_array( $input, self::NAMED_LINE_HEIGHTS, true ) ) {
			return 'var:custom|line-height|' . $input;
		}

		if ( is_numeric( $input ) ) {
			$num = (float) $input;
			if ( $num > 0 && $num <= 5 ) {
				return (string) $num;
			}
		}

		return null;
	}
}

Update_Typography::register();
