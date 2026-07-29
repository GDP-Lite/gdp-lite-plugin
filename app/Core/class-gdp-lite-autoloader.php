<?php
/**
 * Lightweight class autoloader for GDP Lite.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Autoloader {
	/** @var bool */
	private static $registered = false;

	/**
	 * Explicit class map keeps file loading predictable without Composer.
	 *
	 * @var array<string,string>
	 */
	private static $class_map = array(
		'GDP_Lite'                  => 'app/Core/class-gdp-lite.php',
		'GDP_Lite_I18n'             => 'app/Core/class-gdp-lite-i18n.php',
		'GDP_Lite_Post_Types'       => 'app/Core/class-gdp-lite-post-types.php',
		'GDP_Lite_Registry'         => 'app/Core/class-gdp-lite-registry.php',
		'GDP_Lite_Hooks'            => 'app/Core/class-gdp-lite-hooks.php',
		'GDP_Lite_Cache'            => 'app/Core/class-gdp-lite-cache.php',
		'GDP_Lite_Logger'           => 'app/Core/class-gdp-lite-logger.php',
		'GDP_Lite_Upgrader'         => 'app/Core/class-gdp-lite-upgrader.php',
		'GDP_Lite_Health'           => 'app/Core/class-gdp-lite-health.php',
		'GDP_Lite_Admin'            => 'app/Admin/class-gdp-lite-admin.php',
		'GDP_Lite_Meta_Boxes'       => 'app/Admin/class-gdp-lite-meta-boxes.php',
		'GDP_Lite_Shortcodes'       => 'app/Frontend/class-gdp-lite-shortcodes.php',
		'GDP_Lite_Template_Loader'  => 'app/Frontend/class-gdp-lite-template-loader.php',
		'GDP_Lite_Frontend_Filters' => 'app/Frontend/class-gdp-lite-frontend-filters.php',
		'GDP_Lite_Query'            => 'app/Query/class-gdp-lite-query.php',
		'GDP_Lite_REST_API'         => 'app/REST/class-gdp-lite-rest-api.php',
		'GDP_Lite_Blocks'           => 'app/Blocks/class-gdp-lite-blocks.php',
		'GDP_Lite_Importer'         => 'app/Import/class-gdp-lite-importer.php',
		'GDP_Lite_Field'            => 'app/Fields/class-gdp-lite-field.php',
		'GDP_Lite_Field_Registry'   => 'app/Fields/class-gdp-lite-field-registry.php',
		'GDP_Lite_Field_Validator'  => 'app/Fields/class-gdp-lite-field-validator.php',
		'GDP_Lite_Field_Renderer'   => 'app/Fields/class-gdp-lite-field-renderer.php',
		'GDP_Lite_Field_Manager'    => 'app/Fields/class-gdp-lite-field-manager.php',
		'GDP_Lite_Field_Builder'    => 'app/Admin/class-gdp-lite-field-builder.php',
		'GDP_Lite_Data_Portability' => 'app/Import/class-gdp-lite-data-portability.php',
		'GDP_Lite_Template_Engine'  => 'app/Templates/class-gdp-lite-template-engine.php',
		'GDP_Lite_Component_Registry' => 'app/Builder/class-gdp-lite-component-registry.php',
		'GDP_Lite_Layout_Manager'     => 'app/Builder/class-gdp-lite-layout-manager.php',
		'GDP_Lite_Builder_Admin'       => 'app/Builder/class-gdp-lite-builder-admin.php',
		'GDP_Lite_Archive_Builder'     => 'app/Builder/class-gdp-lite-archive-builder.php',
	);

	/** Register the SPL loader once. */
	public static function register() {
		if ( self::$registered ) {
			return;
		}
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
		self::$registered = true;
	}

	/**
	 * Load a mapped GDP class.
	 *
	 * @param string $class Class name.
	 */
	public static function autoload( $class ) {
		if ( ! isset( self::$class_map[ $class ] ) ) {
			return;
		}
		$file = GDP_LITE_DIR . self::$class_map[ $class ];
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
