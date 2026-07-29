<?php
/**
 * Public Dynamic Fields API.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function gdp_register_field( $definition ) { return GDP()->fields()->register( $definition ); }
function gdp_get_field( $post_id, $name, $default = null ) { return GDP()->fields()->get_value( $post_id, $name, $default ); }
function gdp_the_field( $post_id, $name, $default = null ) { echo esc_html( gdp_get_field( $post_id, $name, $default ) ); }
function gdp_has_field( $post_id, $name ) { $value = gdp_get_field( $post_id, $name, null ); return null !== $value && '' !== $value; }
function gdp_update_field( $post_id, $name, $value ) { return GDP()->fields()->update_value( $post_id, $name, $value ); }
function gdp_delete_field( $post_id, $name ) { return GDP()->fields()->delete_value( $post_id, $name ); }
