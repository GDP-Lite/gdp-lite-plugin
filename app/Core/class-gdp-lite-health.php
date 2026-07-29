<?php
/**
 * Runtime health checks.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Health {
	private static $instance = null;
	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register_hooks() { add_filter( 'site_status_tests', array( $this, 'site_health_tests' ) ); }
	public function site_health_tests( $tests ) {
		$tests['direct']['gdp_lite_environment'] = array( 'label' => __( 'GDP Lite environment', 'gdp-lite' ), 'test' => array( $this, 'site_health_result' ) );
		return $tests;
	}
	public function checks() {
		$permalink = (string) get_option( 'permalink_structure', '' );
		return array(
			array( 'label' => 'PHP 8.0+', 'ok' => version_compare( PHP_VERSION, '8.0', '>=' ), 'value' => PHP_VERSION ),
			array( 'label' => 'WordPress 6.4+', 'ok' => version_compare( get_bloginfo( 'version' ), '6.4', '>=' ), 'value' => get_bloginfo( 'version' ) ),
			array( 'label' => 'Pretty permalinks', 'ok' => '' !== $permalink, 'value' => '' !== $permalink ? $permalink : __( 'Plain', 'gdp-lite' ) ),
			array( 'label' => 'REST API', 'ok' => function_exists( 'rest_url' ), 'value' => rest_url( 'gdp/v1/games' ) ),
			array( 'label' => 'Database version', 'ok' => GDP_LITE_DB_VERSION === get_option( 'gdp_lite_db_version' ), 'value' => (string) get_option( 'gdp_lite_db_version', 'Not installed' ) ),
			array( 'label' => 'Object cache', 'ok' => true, 'value' => wp_using_ext_object_cache() ? __( 'Persistent cache active', 'gdp-lite' ) : __( 'Transient fallback', 'gdp-lite' ) ),
		);
	}
	public function site_health_result() {
		$failed = array_filter( $this->checks(), static function( $check ) { return empty( $check['ok'] ); } );
		return array(
			'label' => empty( $failed ) ? __( 'GDP Lite is ready for production', 'gdp-lite' ) : __( 'GDP Lite needs attention', 'gdp-lite' ),
			'status' => empty( $failed ) ? 'good' : 'recommended',
			'badge' => array( 'label' => 'GDP Lite', 'color' => 'blue' ),
			'description' => '<p>' . esc_html( empty( $failed ) ? __( 'Core environment, database version and routing checks passed.', 'gdp-lite' ) : __( 'Open GDP Lite > Health Check for details.', 'gdp-lite' ) ) . '</p>',
			'test' => 'gdp_lite_environment',
		);
	}
}
