<?php
/**
 * Abstract Ability Class
 *
 * Base class for all Spectra One abilities.
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
 * Abstract Class Ability
 */
abstract class Ability {
	/**
	 * Ability ID (e.g. 'spectra-one/get-theme-settings').
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Ability Category.
	 *
	 * @var string
	 */
	protected $category = 'spectra-one';

	/**
	 * Ability Label.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Ability Description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Required capability for this ability.
	 *
	 * @var string
	 */
	protected $capability = 'edit_theme_options';

	/**
	 * Ability Meta Data.
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Tool version.
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->configure();
	}

	/**
	 * Configure the ability (set ID, label, description, etc.).
	 *
	 * @return void
	 */
	abstract public function configure(): void;

	/**
	 * Get the input schema for the ability.
	 *
	 * @return array
	 */
	abstract public function get_input_schema();

	/**
	 * Execute the ability.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	abstract public function execute( $args );

	/**
	 * Get the final input schema.
	 *
	 * @return array
	 */
	public function get_final_input_schema() {
		$schema = $this->get_input_schema();

		if ( ! isset( $schema['properties'] ) ) {
			$schema['properties'] = array();
		}

		return $schema;
	}

	/**
	 * Handle execution with error handling.
	 *
	 * @param array $args Input arguments.
	 * @return array Result array.
	 */
	public function handle_execute( $args ) {
		try {
			return $this->execute( $args );
		} catch ( \Exception $e ) {
			/* translators: %s: error message */
			return $this->handle_error( sprintf( __( 'An unexpected error occurred: %s', 'spectra-one' ), $e->getMessage() ) );
		} catch ( \Error $e ) {
			/* translators: %s: error message */
			return $this->handle_error( sprintf( __( 'A system error occurred: %s', 'spectra-one' ), $e->getMessage() ) );
		}
	}

	/**
	 * Get the output schema for the ability.
	 *
	 * Override in child classes to define the data properties
	 * returned in the success response.
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return array();
	}

	/**
	 * Get usage examples.
	 *
	 * @return array
	 */
	public function get_examples() {
		return array();
	}

	/**
	 * Get tool type (read, write, list).
	 *
	 * @return string
	 */
	public function get_tool_type() {
		return 'read';
	}

	/**
	 * Get whether to show this ability in the REST API.
	 *
	 * Defaults to the unified public flag so `swt_ability_public` acts as the
	 * single exposure switch; use this filter to override REST only.
	 *
	 * @return bool
	 */
	public function get_show_in_rest() {
		/**
		 * Filter whether to show this ability in the REST API.
		 *
		 * @param bool   $show_in_rest     Whether to show in REST API. Defaults to the unified public flag.
		 * @param string $ability_id       The ability ID.
		 * @param self   $ability_instance The ability instance.
		 * @since 1.2.0
		 */
		/** @psalm-suppress TooManyArguments -- WordPress apply_filters accepts variadic args for filter callbacks. */
		return (bool) apply_filters( 'swt_ability_show_in_rest', $this->get_public(), $this->id, $this );
	}

	/**
	 * Get MCP annotations based on tool type.
	 *
	 * Conservative defaults: a write ability CAN destroy or duplicate data,
	 * so it must announce itself as destructive and non-idempotent — MCP
	 * clients gate confirmation on these flags, and a hardcoded
	 * `destructive: false` silently exempted every write tool from that
	 * gate. An ability that is provably additive/idempotent can override
	 * via `$this->meta['annotations']` (merged in register()).
	 *
	 * @return array{readonly: bool, destructive: bool, idempotent: bool}
	 */
	public function get_annotations() {
		$is_write = 'write' === $this->get_tool_type();

		return array(
			'readonly'    => ! $is_write,
			'destructive' => $is_write,
			'idempotent'  => ! $is_write,
		);
	}

	/**
	 * Get whether this ability is publicly exposed to clients.
	 *
	 * Single source of truth for exposure: it is registered as the unified
	 * `public` meta flag introduced in WordPress 7.1 (default false there)
	 * and seeds the defaults of the per-channel flags (`show_in_rest`,
	 * `mcp.public`), so filtering `swt_ability_public` to false hides the
	 * ability everywhere unless a per-channel filter overrides it.
	 *
	 * @since 1.2.6
	 * @return bool
	 */
	public function get_public() {
		/**
		 * Filter whether a Spectra One ability is publicly exposed to clients.
		 *
		 * @since 1.2.6
		 *
		 * @param bool   $is_public        Whether the ability is public. Default true.
		 * @param string $ability_id       The ability ID.
		 * @param self   $ability_instance The ability instance.
		 */
		/** @psalm-suppress TooManyArguments -- WordPress apply_filters accepts variadic args for filter callbacks. */
		return (bool) apply_filters( 'swt_ability_public', true, $this->id, $this );
	}

	/**
	 * Get MCP meta configuration for this ability.
	 *
	 * @return array{public: bool, type: string}
	 */
	public function get_mcp() {
		/**
		 * Filter whether a Spectra One ability is publicly exposed via MCP.
		 *
		 * @since 1.2.0
		 *
		 * @param bool   $is_public        Whether the ability is public for MCP. Defaults to the unified public flag.
		 * @param string $ability_id       The ability ID.
		 * @param self   $ability_instance The ability instance.
		 */
		/** @psalm-suppress TooManyArguments -- WordPress apply_filters accepts variadic args for filter callbacks. */
		$is_public = apply_filters( 'swt_ability_mcp_public', $this->get_public(), $this->id, $this );

		return array(
			'public' => (bool) $is_public,
			'type'   => 'tool',
		);
	}

	/**
	 * Check permissions.
	 *
	 * @param \WP_REST_Request $request REST Request.
	 * @return bool|\WP_Error
	 */
	public function check_permission( $request ) {
		return current_user_can( $this->capability );
	}

	/**
	 * Get the ability ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the ability label.
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Get the ability description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Get the category.
	 *
	 * @return string
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Get the meta data.
	 *
	 * @return array
	 */
	public function get_meta_data() {
		return $this->meta;
	}

	/**
	 * Get the tool version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Register this ability with the Abilities API.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		/** @psalm-suppress UnsafeInstantiation -- Intentional: child classes share the same constructor signature. */
		$instance = new static();

		if ( empty( $instance->id ) ) {
			return;
		}

		$meta = array(
			'tool_type'    => $instance->get_tool_type(),
			'examples'     => $instance->get_examples(),
			'version'      => $instance->get_version(),
			'public'       => $instance->get_public(),
			'show_in_rest' => $instance->get_show_in_rest(),
			'annotations'  => $instance->get_annotations(),
			'mcp'          => $instance->get_mcp(),
		);

		$meta = array_replace_recursive( $meta, $instance->meta );

		$args = array(
			'label'               => $instance->label,
			'description'         => $instance->description,
			'category'            => $instance->category,
			'input_schema'        => $instance->get_final_input_schema(),
			'execute_callback'    => array( $instance, 'handle_execute' ),
			'permission_callback' => array( $instance, 'check_permission' ),
			'meta'                => $meta,
		);

		$output_schema = $instance->get_output_schema();
		if ( ! empty( $output_schema ) ) {
			$args['output_schema'] = $output_schema;
		}

		wp_register_ability( $instance->id, $args );
	}

	/**
	 * Build a standardized output schema wrapping data properties
	 * in the Swt_Abilities_Response::success() format.
	 *
	 * @param array $data_properties Properties for the 'data' key.
	 * @return array Full output schema.
	 */
	protected function build_output_schema( $data_properties ) {
		return array(
			'type'       => 'object',
			'required'   => array( 'success', 'message' ),
			'properties' => array(
				'success' => array(
					'type'        => 'boolean',
					'description' => 'Whether the operation succeeded.',
				),
				'message' => array(
					'type'        => 'string',
					'description' => 'Human-readable result message.',
				),
				'data'    => array(
					'type'       => 'object',
					'properties' => $data_properties,
				),
			),
		);
	}

	/**
	 * Handle error response, showing details only in debug mode.
	 *
	 * @param string $message       The detailed error message.
	 * @param string $generic_message Optional generic message for non-admin users.
	 * @return array Error response array.
	 */
	private function handle_error( $message, $generic_message = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Spectra One Ability Error: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
		}

		// Show detailed error messages to administrators, but generic message to others.
		if ( current_user_can( 'manage_options' ) ) {
			return Response::error( $message );
		}

		$fallback = ! empty( $generic_message ) ? $generic_message : __( 'An unexpected error occurred.', 'spectra-one' );
		return Response::error( $fallback );
	}
}
