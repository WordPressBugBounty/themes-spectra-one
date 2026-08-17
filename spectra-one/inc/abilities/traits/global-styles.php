<?php
/**
 * Global Styles Trait
 *
 * Shared read/write helpers for abilities that touch the FSE
 * wp_global_styles post (color palette, typography, fonts).
 *
 * @package Spectra One
 * @subpackage Abilities
 * @since 1.2.0
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
	 * Every consumer of this helper is a WRITE ability, so a site that never
	 * opened the Site Editor (no user global-styles post yet) gets the post
	 * created here the same way core does, instead of failing.
	 *
	 * @return array{ID: int, styles: array}|\WP_Error|null WP_Error when the
	 *         post exists but its JSON is unreadable (corruption — never write
	 *         over it); null when the post is absent and cannot be created.
	 */
	protected function get_global_styles() {
		$db_styles = \Swt\get_theme_custom_styles();
		$post_id   = (int) ( $db_styles['ID'] ?? 0 );

		if ( 0 === $post_id ) {
			$post_id = (int) \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
			if ( $post_id <= 0 ) {
				return null;
			}
			// Freshly seeded by core: just the schema markers, no styles yet —
			// save_global_styles() re-stamps the markers on every write.
			return array(
				'ID'     => $post_id,
				'styles' => array(),
			);
		}

		// A null post_content means the post exists but its JSON would not
		// decode. Writing from here would replace the user's whole style layer
		// with only what this ability mints — fail closed, and say WHICH
		// failure this is: corruption needs the operator's attention, "no
		// styles yet" doesn't.
		// NOTE: no `?? array()` here — `??` treats null as absent and would
		// silently re-arm the exact wipe this guard exists to prevent.
		$styles = array_key_exists( 'post_content', $db_styles ) ? $db_styles['post_content'] : null;
		if ( ! is_array( $styles ) ) {
			return new \WP_Error(
				'swt_global_styles_unreadable',
				__( 'The global styles post exists but its content is not readable JSON — refusing to write over it.', 'spectra-one' )
			);
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
		// WordPress only honours a user global-styles post when these markers are
		// present: WP_Theme_JSON_Resolver::get_user_data() discards the ENTIRE
		// post content unless `isGlobalStylesUserThemeJSON` is true, and
		// WP_Theme_JSON expects a schema `version`. Without them the saved
		// palette / typography / fonts are silently ignored (post saves fine, but
		// nothing renders in the Site Editor or on the frontend).
		// theme.json schema v2 — universally supported; WP auto-migrates to its
		// latest internally and overrides this value when reading user data, so a
		// fixed 2 is safe across WP versions (and avoids depending on the
		// WP_Theme_JSON::LATEST_VERSION constant, absent in some releases).
		$styles['version']                     = 2;
		$styles['isGlobalStylesUserThemeJSON'] = true;

		$json = wp_json_encode( $styles );
		if ( false === $json ) {
			return new \WP_Error(
				'swt_ability_json_encode_failed',
				__( 'Failed to encode global styles as JSON.', 'spectra-one' )
			);
		}

		// wp_update_post() runs wp_unslash() on the data — without wp_slash()
		// the backslashes in JSON escape sequences (quotes in names, unicode
		// escapes) are stripped and the stored JSON no longer decodes.
		return wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_slash( $json ),
			),
			true
		);
	}
}
