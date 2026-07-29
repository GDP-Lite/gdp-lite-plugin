<?php /** GDP Template. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
GDP_Lite_Hooks::action( 'before_archive' );
GDP_Lite_Template_Loader::get_template( 'parts/archive-content.php', array( 'title' => post_type_archive_title( '', false ), 'description' => get_the_archive_description() ) );
GDP_Lite_Hooks::action( 'after_archive' );
get_footer();
