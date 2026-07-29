<?php
/**
 * GDP Lite uninstall handler.
 *
 * Data is retained intentionally to prevent accidental loss.
 *
 * @package GDPLite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
delete_option( 'gdp_default_columns' );
