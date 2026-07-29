<?php
if(!defined('ABSPATH')){exit;} function gdp_render_field($name,$post_id=0,$args=array()){return GDP()->renderer()->render_field($name,$post_id,$args);} function gdp_render_fields($context='single',$post_id=0){return GDP()->renderer()->render_group($context,$post_id);} function gdp_render_card($post_id=0){return GDP()->renderer()->render_card($post_id);}
