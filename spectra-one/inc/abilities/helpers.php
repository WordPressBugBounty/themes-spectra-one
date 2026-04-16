<?php
/**
 * Ability Helpers
 *
 * Generic utilities shared by Spectra One abilities. Kept deliberately
 * small and side-effect free so individual abilities can opt into them
 * without coupling the base class to any particular domain.
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
 * Class Helpers
 */
final class Helpers {
	/**
	 * Ensure a nested path exists inside an array, creating empty arrays
	 * at any missing level.
	 *
	 * @param array<string, mixed> $arr  Array to mutate in place.
	 * @param array<int, string>   $path Nested keys in order.
	 * @return void
	 */
	public static function ensure_nested( array &$arr, array $path ): void {
		$ref = &$arr;
		foreach ( $path as $key ) {
			if ( ! isset( $ref[ $key ] ) || ! is_array( $ref[ $key ] ) ) {
				$ref[ $key ] = array();
			}
			$ref = &$ref[ $key ];
		}
	}

	/**
	 * Encode data as a JSON string, returning an empty string on failure.
	 *
	 * Convenience wrapper for contexts where a non-null string is required
	 * (e.g. building block markup via concatenation). Callers that need to
	 * distinguish success from failure must use wp_json_encode() directly.
	 *
	 * @param mixed $data Data to encode.
	 * @return string JSON string, or empty string if encoding failed.
	 */
	public static function safe_json_encode( $data ): string {
		$json = wp_json_encode( $data );
		return false === $json ? '' : $json;
	}
}
