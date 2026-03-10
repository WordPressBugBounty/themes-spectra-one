<?php
/**
 * Meta Box
 *
 * @package Spectra One
 * @author Brainstorm Force
 * @since 0.0.1
 */

declare(strict_types=1);

namespace Swt;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'init', SWT_NS . 'register_meta_settings' );

/**
 * Auth callback for post meta.
 *
 * Ensures only users who can edit the specific post can modify its meta.
 *
 * @since 1.1.8
 * @param bool   $allowed  Whether the user can add the post meta.
 * @param string $meta_key The meta key.
 * @param int    $post_id  Post ID.
 * @return bool
 */
function meta_auth_callback( bool $allowed, string $meta_key, int $post_id ): bool {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Register Post Meta options for react based fields.
 *
 * @since 0.0.1
 * @return void
 */
function register_meta_settings(): void {
	$meta_fields = array(
		'_swt_meta_header_display',
		'_swt_meta_footer_display',
		'_swt_meta_site_title_display',
		'_swt_meta_sticky_header',
		'_swt_meta_transparent_header',
	);

	// Register each meta field with edit_post capability check via auth_callback.
	foreach ( $meta_fields as $meta_key ) {
		register_post_meta(
			'',
			$meta_key,
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'boolean',
				'auth_callback' => SWT_NS . 'meta_auth_callback',
			)
		);
	}
}
