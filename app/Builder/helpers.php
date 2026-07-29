<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function gdp_render_layout( $layout = 'card', $post_id = 0, $args = array() ) { return GDP()->builder()->render( $layout, $post_id, $args ); }
function gdp_register_component( $id, $definition ) { return GDP()->components()->register( $id, $definition ); }
