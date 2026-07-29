<?php
/**
 * GDP Lite application bootstrap.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite {
	/** @var GDP_Lite|null */
	private static $instance = null;

	/** @var GDP_Lite_Registry */
	private $registry;

	/** @var bool */
	private $booted = false;

	/** @return GDP_Lite */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->registry = new GDP_Lite_Registry();
	}

	/** Boot all first-party modules once. */
	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		$modules = array(
			'hooks'           => GDP_Lite_Hooks::instance(),
			'cache'           => GDP_Lite_Cache::instance(),
			'logger'          => GDP_Lite_Logger::instance(),
			'upgrader'        => GDP_Lite_Upgrader::instance(),
			'health'          => GDP_Lite_Health::instance(),
			'i18n'            => GDP_Lite_I18n::instance(),
			'post_types'       => GDP_Lite_Post_Types::instance(),
			'fields'           => GDP_Lite_Field_Manager::instance(),
			'field_builder'    => GDP_Lite_Field_Builder::instance(),
			'components'       => GDP_Lite_Component_Registry::instance(),
			'builder'          => GDP_Lite_Layout_Manager::instance(),
			'builder_admin'    => GDP_Lite_Builder_Admin::instance(),
			'archive_builder'  => GDP_Lite_Archive_Builder::instance(),
			'renderer'         => GDP_Lite_Template_Engine::instance(),
			'meta_boxes'       => GDP_Lite_Meta_Boxes::instance(),
			'admin'            => GDP_Lite_Admin::instance(),
			'shortcodes'       => GDP_Lite_Shortcodes::instance(),
			'importer'         => GDP_Lite_Importer::instance(),
			'templates'        => GDP_Lite_Template_Loader::instance(),
			'frontend_filters' => GDP_Lite_Frontend_Filters::instance(),
			'rest'             => GDP_Lite_REST_API::instance(),
			'blocks'           => GDP_Lite_Blocks::instance(),
		);

		foreach ( $modules as $id => $module ) {
			$this->registry->set( $id, $module );
			if ( is_callable( array( $module, 'register_hooks' ) ) ) {
				$module->register_hooks();
			}
		}

		GDP_Lite_Hooks::action( 'loaded', $this );
	}

	/** Load translations. */
	public function load_textdomain() {
		load_plugin_textdomain( 'gdp-lite', false, dirname( plugin_basename( GDP_LITE_FILE ) ) . '/languages' );
	}

	/** @return GDP_Lite_Registry */
	public function services() {
		return $this->registry;
	}

	/** @return object|null */
	public function service( $id ) {
		return $this->registry->get( $id );
	}

	/** Stable convenience accessors. */
	public function query() { return 'GDP_Lite_Query'; }
	public function cache() { return $this->service( 'cache' ); }
	public function logger() { return $this->service( 'logger' ); }
	public function health() { return $this->service( 'health' ); }
	public function fields() { return $this->service( 'fields' ); }
	public function rest() { return $this->service( 'rest' ); }
	public function templates() { return $this->service( 'templates' ); }
	public function importer() { return $this->service( 'importer' ); }
	public function blocks() { return $this->service( 'blocks' ); }
	public function renderer() { return $this->service( 'renderer' ); }
	public function builder() { return $this->service( 'builder' ); }
	public function components() { return $this->service( 'components' ); }
	public function archive_builder() { return $this->service( 'archive_builder' ); }
	public function exporter() { return $this->service( 'importer' ); }

	/** Plugin activation. */
	public static function activate() {
		GDP_Lite_I18n::instance()->register_hooks();
		GDP_Lite_Post_Types::instance()->register();
		GDP_Lite_Upgrader::activate();
		if ( false === get_option( 'gdp_lite_cache_version', false ) ) { update_option( 'gdp_lite_cache_version', 1, false ); }
		flush_rewrite_rules();
	}

	/** Plugin deactivation. */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
