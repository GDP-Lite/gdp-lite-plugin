<?php /** GDP Template. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
GDP_Lite_Template_Loader::get_template( 'parts/archive-content.php', array( 'title' => single_term_title( '', false ), 'description' => term_description() ) );
get_footer();
