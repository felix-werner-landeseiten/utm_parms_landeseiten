<?php
/**
 * Plugin Name:       UTM Parameter Forwarder
 * Plugin URI:        https://github.com/felix-werner-landeseiten/utm_parms_landeseiten
 * Description:       Carries UTM and ad click-ID parameters (Google, Meta, LinkedIn) from the landing URL to every internal link, so forms and tracking on inner pages can read them.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Landeseiten
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       utm-parameter-forwarder
 * Update URI:        https://github.com/felix-werner-landeseiten/utm_parms_landeseiten
 *
 * @package UTM_Parameter_Forwarder
 */

// Block direct access to this file.
defined( 'ABSPATH' ) || exit;

// Single source of truth for the version. Keep in sync with the "Version" header above.
define( 'UTMPF_VERSION', '1.0.0' );
define( 'UTMPF_FILE', __FILE__ );
define( 'UTMPF_DIR', plugin_dir_path( __FILE__ ) );
define( 'UTMPF_URL', plugin_dir_url( __FILE__ ) );
define( 'UTMPF_SLUG', 'utm-parameter-forwarder' );

require_once UTMPF_DIR . 'includes/class-utmpf-settings.php';
require_once UTMPF_DIR . 'includes/class-utmpf-frontend.php';
require_once UTMPF_DIR . 'includes/class-utmpf-updater.php';

UTMPF_Settings::init();
UTMPF_Frontend::init();
UTMPF_Updater::init();
