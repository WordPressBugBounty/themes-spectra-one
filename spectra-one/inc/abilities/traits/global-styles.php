<?php
/**
 * Global Styles Trait
 *
 * Shared read/write helpers for abilities that touch the FSE
 * wp_global_styles post (color palette, typography, fonts).
 *
 * @package Spectra One
 * @subpackage Abilities
 * @since x.x.x
 */

declare( strict_types=1 );

namespace Swt\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Global_Styles
 */
trait Global_Styles {
	/**
	 * Load the global styles post and its decoded content.
	 *
	 * @return array{ID: int, styles: array}|null Null when no global styles
	 *         post exists (e.g. non-FSE theme active).
	 */
	protected function get_global_styles() {
		$db_styles = \Swt\get_theme_custom_styles();
		$post_id   = (int) ( $db_styles['ID'] ?? 0 );

		if ( 0 === $post_id ) {
			return null;
		}

		$styles = $db_styles['post_content'] ?? array();
		if ( ! is_array( $styles ) ) {
			$styles = array();
		}

		return array(
			'ID'     => $post_id,
			'styles' => $styles,
		);
	}

	/**
	 * Persist global styles back to the wp_global_styles post.
	 *
	 * @param int                  $post_id Global styles post ID.
	 * @param array<string, mixed> $styles  Global styles data to encode.
	 * @return int|\WP_Error Post ID on success, WP_Error on encode/update failure.
	 */
	protected function save_global_styles( int $post_id, array $styles ) {
		$json = wp_json_encode( $styles );
		if ( false === $json ) {
			return new \WP_Error(
				'swt_ability_json_encode_failed',
				__( 'Failed to encode global styles as JSON.', 'spectra-one' )
			);
		}

		return wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $json,
			),
			true
		);
	}
}
