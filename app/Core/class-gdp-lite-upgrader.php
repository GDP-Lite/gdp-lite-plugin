<?php
/**
 * Versioned database and capability upgrades.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Upgrader {
	private static $instance = null;
	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register_hooks() { add_action( 'admin_init', array( $this, 'maybe_upgrade' ), 1 ); }
	public static function activate() { self::instance()->run(); }
	public function maybe_upgrade() {
		if ( version_compare( (string) get_option( 'gdp_lite_db_version', '0' ), GDP_LITE_DB_VERSION, '<' ) ) { $this->run(); }
	}
	public function run() {
		$this->install_capabilities();
		$this->install_defaults();
		update_option( 'gdp_lite_version', GDP_LITE_VERSION, false );
		update_option( 'gdp_lite_db_version', GDP_LITE_DB_VERSION, false );
		do_action( 'gdp_database_upgraded', GDP_LITE_DB_VERSION );
	}
	private function install_defaults() {
		if ( false === get_option( 'gdp_lite_cache_version', false ) ) { add_option( 'gdp_lite_cache_version', 1, '', false ); }
		if ( false === get_option( 'gdp_lite_performance', false ) ) { add_option( 'gdp_lite_performance', array( 'cache_enabled' => 1, 'ttl' => 3600 ), '', false ); }
		if ( false === get_option( 'gdp_builder_version', false ) ) { add_option( 'gdp_builder_version', 1, '', false ); }
		if ( false === get_option( GDP_Lite_Layout_Manager::OPTION, false ) ) { add_option( GDP_Lite_Layout_Manager::OPTION, GDP_Lite_Layout_Manager::instance()->defaults(), '', false ); }
	}
	private function install_capabilities() {
		$admin_caps = array( 'manage_gdp', 'edit_games', 'delete_games', 'import_games', 'export_games', 'manage_gdp_fields', 'manage_gdp_builder' );
		$editor_caps = array( 'edit_games', 'delete_games' );
		foreach ( array( 'administrator' => $admin_caps, 'editor' => $editor_caps ) as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( ! $role ) { continue; }
			foreach ( $caps as $cap ) { $role->add_cap( $cap ); }
		}
	}
}
