<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class GDP_Lite_Data_Portability {
 public static function normalize_item($item){ if(isset($item['fields'])&&is_array($item['fields'])){$item=array_merge($item,$item['fields']);} return apply_filters('gdp_import_mapping',$item); }
 public static function import_fields($post_id,$item){ $item=self::normalize_item($item); $errors=array(); foreach(GDP()->fields()->all() as $field){if(!$field->get('import',true)||!array_key_exists($field->name(),$item)){continue;} $r=GDP()->fields()->update_value($post_id,$field->name(),$item[$field->name()]); if(is_wp_error($r)){$errors[$field->name()]=$r->get_error_message();} do_action('gdp_import_after_field',$post_id,$field,$item[$field->name()],$r);} return $errors; }
 public static function export_fields($post_id){$out=array(); foreach(GDP()->fields()->all() as $field){if($field->get('export',true)){$out[$field->name()]=GDP()->fields()->get_value($post_id,$field->name());}} return apply_filters('gdp_export_fields',$out,$post_id);}
 public static function schema(){ $schema=array('schema_version'=>GDP()->fields()->version(),'plugin_version'=>GDP_LITE_VERSION,'fields'=>array()); foreach(GDP()->fields()->all() as $field){$schema['fields'][$field->name()]=$field->to_array();} return $schema; }
}
