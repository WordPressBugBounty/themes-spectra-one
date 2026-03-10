<?php
/**
 * Spectra One Abilities API Bootstrap
 *
 * Loads the Abilities API integration and initializes
 * the Spectra One abilities registration.
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
 * Initialize Spectra One Abilities.
 *
 * @return void
 */
function init(): void {
	$abilities_dir = \Swt\SWT_DIR . 'inc/abilities/';

	// Load base classes.
	require_once $abilities_dir . 'response.php';
	require_once $abilities_dir . 'ability.php';
	require_once $abilities_dir . 'init.php';

	// Initialize abilities registration.
	Init::get_instance();
}

// Initialize after theme setup so Swt functions are available.
add_action( 'after_setup_theme', __NAMESPACE__ . '\\init' );
