<?php /** GDP Template Part. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
the_posts_pagination( array( 'mid_size'=>1, 'prev_text'=>__( 'Previous', 'gdp-lite' ), 'next_text'=>__( 'Next', 'gdp-lite' ) ) );
