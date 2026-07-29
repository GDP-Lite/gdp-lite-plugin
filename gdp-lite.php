<?php
/**
 * Plugin Name: GDP Lite
 * Plugin URI:  https://example.com/gdp-lite
 * Description: Lightweight gaming database engine for WordPress.
 * Version:     2.2.1
 * Author:      GDP
 * Text Domain: gdp-lite
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GDP_LITE_VERSION', '2.2.1' );
define( 'GDP_LITE_DB_VERSION', '2.2.1' );
define( 'GDP_LITE_FILE', __FILE__ );
define( 'GDP_LITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'GDP_LITE_URL', plugin_dir_url( __FILE__ ) );
define( 'GDP_LITE_BASENAME', plugin_basename( __FILE__ ) );

require_once GDP_LITE_DIR . 'app/Core/class-gdp-lite-autoloader.php';
GDP_Lite_Autoloader::register();
require_once GDP_LITE_DIR . 'app/Fields/helpers.php';
require_once GDP_LITE_DIR . 'app/Templates/helpers.php';
require_once GDP_LITE_DIR . 'app/Builder/helpers.php';

/**
 * Stable application accessor.
 *
 * @return GDP_Lite
 */
function GDP() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return GDP_Lite::instance();
}

/** Backward-friendly lowercase alias. @return GDP_Lite */
function gdp_lite() {
	return GDP();
}

register_activation_hook( GDP_LITE_FILE, array( 'GDP_Lite', 'activate' ) );
register_deactivation_hook( GDP_LITE_FILE, array( 'GDP_Lite', 'deactivate' ) );

GDP()->boot();
