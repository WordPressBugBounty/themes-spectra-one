<?php
/**
 * Global styles compatibility
 *
 * @package Spectra One
 * @author Brainstorm Force
 * @since 1.2.4
 */

declare(strict_types=1);

namespace Swt;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'wp_theme_json_data_user', SWT_NS . 'filter_user_font_size_presets' );

/**
 * Normalise legacy font size presets as global styles are read.
 *
 * Corrected on read rather than in the stored post: saving this post from a
 * front-end request runs core's sanitiser, which strips custom CSS from anyone
 * without edit_css.
 *
 * @param \WP_Theme_JSON_Data $theme_json User global styles data.
 *
 * @return \WP_Theme_JSON_Data
 * @since 1.2.4
 */
function filter_user_font_size_presets( $theme_json ) {
	if ( ! $theme_json instanceof \WP_Theme_JSON_Data ) {
		return $theme_json;
	}

	$normalised = normalise_legacy_font_size_presets( $theme_json->get_data() );

	if ( null === $normalised ) {
		return $theme_json;
	}

	return $theme_json->update_with( $normalised );
}

/**
 * Make legacy `clamp()` preset sizes parseable, leaving fluid values alone.
 *
 * Core cannot parse `clamp()`, so it ignores the preset's fluid min/max. Its own
 * upper bound is enough for core to rebuild the clamp. `fluid` is deliberately
 * untouched: the editor keeps the stale `size` when saving, so overwriting it
 * would discard every edit the user makes.
 *
 * @param array $data Decoded global styles data.
 *
 * @return array|null Modified data, or null when there was nothing to change.
 * @since 1.2.4
 */
function normalise_legacy_font_size_presets( array $data ): ?array {
	if ( ! isset( $data['settings']['typography']['fontSizes']['theme'] ) || ! is_array( $data['settings']['typography']['fontSizes']['theme'] ) ) {
		return null;
	}

	$changed = false;

	foreach ( $data['settings']['typography']['fontSizes']['theme'] as $index => $preset ) {
		if ( ! is_array( $preset ) || ! isset( $preset['size'] ) || ! is_string( $preset['size'] ) ) {
			continue;
		}

		if ( 0 !== strpos( trim( $preset['size'] ), 'clamp(' ) ) {
			continue;
		}

		$max = clamp_max_value( $preset['size'] );

		if ( null === $max ) {
			continue;
		}

		$data['settings']['typography']['fontSizes']['theme'][ $index ]['size'] = $max;

		$changed = true;
	}

	return $changed ? $data : null;
}

/**
 * Parse the upper bound out of a `clamp()` expression.
 *
 * @param string $size Legacy `clamp( min, preferred, max )` value.
 *
 * @return string|null Plain CSS length, or null when it could not be parsed.
 * @since 1.2.4
 */
function clamp_max_value( string $size ): ?string {
	if ( preg_match( '/,\s*(\d*\.?\d+(?:px|rem|em))\s*\)\s*$/', trim( $size ), $matches ) ) {
		return $matches[1];
	}

	return null;
}
