<?php
/**
 * Theme updater
 *
 * @package Spectra One
 * @author Brainstorm Force
 * @since 1.0.5
 */

declare(strict_types=1);

namespace Swt;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Regenerate spectra one.
 *
 * @return void
 * @since 1.0.5
 */
function run_function_after_theme_update(): void {
	$version          = wp_get_theme()->get( 'Version' );
	$update_callbacks = backward_compatibility_update_callbacks();

	if ( $version ) {
		/** @psalm-suppress PossiblyInvalidArgument */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- $version comes from wordpress function itself.
		[ $current_version ] = explode( '-', $version );
		$old_version         = get_option( 'swt_theme_version' );

		if ( $old_version !== $current_version && $old_version < $current_version ) {
			// Run your function here.

			updater_spectra_plugin_fonts();
			updater_custom_fonts_plugin();
			migrate_global_styles_font_size_presets();

			if ( false !== $old_version && isset( $update_callbacks[ $current_version ] ) ) {
				/**
				 * Run the backward compatibility callback for the old users
				 * according to the theme's current version.
				 */
				call_user_func( $update_callbacks[ $current_version ] );
			}

			// Update not to run twice.
			update_option( 'swt_theme_version', $current_version );
		}
	}
}
add_action( 'after_setup_theme', SWT_NS . 'run_function_after_theme_update' );

/**
 * Implement theme update logic.
 *
 * @return void
 * @since 1.0.5
 */
function remove_option_after_theme_update(): void {
	delete_option( 'swt_theme_version' );
}
add_action( 'switch_theme', SWT_NS . 'remove_option_after_theme_update' );

/**
 * Update fonts for Spectra Legacy.
 *
 * This function checks if Spectra Legacy is active,
 * retrieves the Spectra global FSE fonts option from the admin settings,
 * and saves Google fonts to the theme using the UAGB_FSE_Fonts_Compatibility class.
 *
 * @return void
 */
function updater_spectra_plugin_fonts(): void {

	if ( is_spectra_legacy_active() ) {
		/** @psalm-suppress UndefinedClass */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Needed to Regenerate fonts.
		$spectra_global_fse_fonts = \UAGB_Admin_Helper::get_admin_settings_option( 'spectra_global_fse_fonts', array() );

		if ( empty( $spectra_global_fse_fonts ) || ! is_array( $spectra_global_fse_fonts ) ) {
			return;
		}
		/** @psalm-suppress UndefinedClass */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Needed to Regenerate fonts.
		$uagb_fonts = new \UAGB_FSE_Fonts_Compatibility();

		/** @psalm-suppress UndefinedClass */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Needed to Regenerate fonts.
		$uagb_fonts->save_google_fonts_to_theme();
	}
}

/**
 * Update fonts for the custom fonts plugin.
 *
 * This function checks if the custom fonts plugin is active,
 * retrieves all existing font posts,
 * and updates the FSE theme JSON using the bcf_google_fonts_compatibility() function.
 *
 * @return void
 */
function updater_custom_fonts_plugin(): void {

	/** @psalm-suppress UndefinedFunction */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Needed to Regenerate fonts.
	$is_custom_font_plugin = is_custom_fonts_plugin();
	if ( $is_custom_font_plugin ) {
		/** @psalm-suppress UndefinedClass */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Needed to Regenerate fonts.
		$all_fonts = \Bsf_Custom_Fonts_Render::get_instance()->get_existing_font_posts();

		if ( empty( $all_fonts ) || ! is_array( $all_fonts ) ) {
			return;
		}
		// @codingStandardsIgnoreStart
		/**
		 * @psalm-suppress UndefinedClass
		 * @psalm-suppress UndefinedFunction
		 */
		bcf_google_fonts_compatibility()->update_fse_theme_json();
		// @codingStandardsIgnoreEnd
	}
}

/**
 * Callback functions to run for backward compatibility upgrade process.
 *
 * @return array
 */
function backward_compatibility_update_callbacks() {
	return array(
		'1.1.1' => SWT_NS . 'backward_compatibility_1_1_1',
	);
}

/**
 * Repair legacy font size presets stored in the user's global styles.
 *
 * Older versions stored a `clamp()` string in `size`, which core cannot parse,
 * so any fluid min/max set in the Site Editor never reached the frontend. Those
 * presets are reset to their theme.json defaults; applying the stored min/max
 * instead would resize live sites that currently look correct.
 *
 * @return void
 * @since x.x.x
 */
function migrate_global_styles_font_size_presets(): void {
	// Not get_user_global_styles_post_id() -- that creates a post as a side effect.
	$user_cpt = \WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles( wp_get_theme() );

	if ( empty( $user_cpt['ID'] ) || ! isset( $user_cpt['post_content'] ) || ! is_string( $user_cpt['post_content'] ) ) {
		return;
	}

	$post_id = (int) $user_cpt['ID'];
	$data    = json_decode( $user_cpt['post_content'], true );

	if ( ! is_array( $data ) || ! isset( $data['settings']['typography']['fontSizes']['theme'] ) || ! is_array( $data['settings']['typography']['fontSizes']['theme'] ) ) {
		return;
	}

	$defaults = theme_font_size_presets();
	$changed  = false;

	foreach ( $data['settings']['typography']['fontSizes']['theme'] as $index => $preset ) {
		if ( ! is_array( $preset ) || ! isset( $preset['size'] ) || ! is_string( $preset['size'] ) ) {
			continue;
		}

		// A plain size already renders correctly.
		if ( 0 !== strpos( trim( $preset['size'] ), 'clamp(' ) ) {
			continue;
		}

		$slug = isset( $preset['slug'] ) && is_string( $preset['slug'] ) ? $preset['slug'] : '';

		if ( isset( $defaults[ $slug ]['size'] ) ) {
			$data['settings']['typography']['fontSizes']['theme'][ $index ]['size'] = $defaults[ $slug ]['size'];

			if ( isset( $defaults[ $slug ]['fluid'] ) ) {
				$data['settings']['typography']['fontSizes']['theme'][ $index ]['fluid'] = $defaults[ $slug ]['fluid'];
			} else {
				unset( $data['settings']['typography']['fontSizes']['theme'][ $index ]['fluid'] );
			}

			$changed = true;
			continue;
		}

		// Preset the theme no longer ships -- fall back to the clamp's upper bound.
		$max = clamp_max_value( $preset['size'] );

		if ( null === $max ) {
			continue;
		}

		$data['settings']['typography']['fontSizes']['theme'][ $index ]['size'] = $max;

		$changed = true;
	}

	if ( $changed ) {
		wp_update_post(
			array(
				'ID'           => $post_id,
				// Same flags core uses for this post type, so the JSON survives kses.
				'post_content' => (string) wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ),
			)
		);
	}
}

/**
 * Font size presets defined by the theme, keyed by slug.
 *
 * Read from resolved theme.json data so child theme overrides are respected.
 *
 * @return array<string, array> Presets keyed by slug.
 * @since x.x.x
 */
function theme_font_size_presets(): array {
	$settings = \WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
	$presets  = $settings['typography']['fontSizes']['theme'] ?? array();

	if ( ! is_array( $presets ) ) {
		return array();
	}

	$by_slug = array();

	foreach ( $presets as $preset ) {
		if ( is_array( $preset ) && isset( $preset['slug'], $preset['size'] ) && is_string( $preset['slug'] ) ) {
			$by_slug[ $preset['slug'] ] = $preset;
		}
	}

	return $by_slug;
}

/**
 * Parse the upper bound out of a `clamp()` expression.
 *
 * @param string $size Legacy `clamp( min, preferred, max )` value.
 *
 * @return string|null Plain CSS length, or null when it could not be parsed.
 * @since x.x.x
 */
function clamp_max_value( string $size ): ?string {
	if ( preg_match( '/,\s*(\d*\.?\d+(?:px|rem|em))\s*\)\s*$/', trim( $size ), $matches ) ) {
		return $matches[1];
	}

	return null;
}

/**
 * Handle backward compatibility for v1.1.1
 *
 * @return void
 * @since 1.1.1
 */
function backward_compatibility_1_1_1(): void {
	$swt_theme_options = get_option( 'swt_theme_options', array() );

	if ( ! isset( $swt_theme_options['enable_default_spacing_paddings'] ) ) {
		$swt_theme_options['enable_default_spacing_paddings'] = true;

		update_option( 'swt_theme_options', $swt_theme_options );
	}
}
