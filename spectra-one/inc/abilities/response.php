<?php
/**
 * Ability Response Helper
 *
 * Standardized response format for all Spectra One abilities.
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
 * Class Response
 *
 * Enforces consistent response format for all abilities.
 */
final class Response {
	/**
	 * Create a success response.
	 *
	 * @param string $message Success message.
	 * @param array  $data    Optional additional data.
	 * @return array Standardized success response.
	 */
	public static function success( $message, $data = array() ) {
		$response = array(
			'success' => true,
			'message' => $message,
		);

		if ( ! empty( $data ) ) {
			$response['data'] = $data;
		}

		return $response;
	}

	/**
	 * Create an error response.
	 *
	 * @param string $message    Error message.
	 * @param string $suggestion Optional suggestion.
	 * @return array Standardized error response.
	 */
	public static function error( $message, $suggestion = '' ) {
		$response = array(
			'success' => false,
			'message' => $message,
		);

		if ( ! empty( $suggestion ) ) {
			$response['suggestion'] = $suggestion;
		}

		return $response;
	}

	/**
	 * Create an error response from WP_Error.
	 *
	 * @param \WP_Error|mixed $wp_error WordPress error object.
	 * @return array Standardized error response.
	 */
	public static function from_wp_error( $wp_error ) {
		/** @psalm-suppress DocblockTypeContradiction -- Defensive check for non-WP_Error callers. */
		if ( ! is_wp_error( $wp_error ) ) {
			return self::error( __( 'An unknown error occurred.', 'spectra-one' ) );
		}

		return self::error( $wp_error->get_error_message() );
	}
}
