<?php
/** Front-end shortcodes. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Shortcodes {
	private static $instance = null;
	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register_hooks() { add_shortcode( 'gdp_games', array( $this, 'games' ) ); add_shortcode( 'gdp_game_filters', array( $this, 'filters' ) ); add_shortcode( 'gdp_field', array( $this, 'field' ) ); add_shortcode( 'gdp_fields', array( $this, 'fields' ) ); add_shortcode( 'gdp_card', array( $this, 'card' ) ); add_shortcode( 'gdp_related', array( $this, 'related' ) ); }


 public function field($atts){$a=shortcode_atts(array('name'=>'','id'=>0,'label'=>'true'),$atts,'gdp_field');return GDP()->renderer()->render_field(sanitize_key($a['name']),absint($a['id']),array('show_label'=>filter_var($a['label'],FILTER_VALIDATE_BOOLEAN)));}
 public function fields($atts){$a=shortcode_atts(array('context'=>'single','id'=>0),$atts,'gdp_fields');return GDP()->renderer()->render_group(sanitize_key($a['context']),absint($a['id']));}
 public function card($atts){$a=shortcode_atts(array('id'=>0),$atts,'gdp_card');return GDP()->renderer()->render_card(absint($a['id']));}
 public function related($atts){$a=shortcode_atts(array('id'=>0,'limit'=>4),$atts,'gdp_related');$id=absint($a['id'])?:get_the_ID();$terms=wp_get_post_terms($id,'gdp_provider',array('fields'=>'slugs'));$q=new GDP_Lite_Query(array('provider'=>$terms,'limit'=>absint($a['limit'])));$html='<div class="gdp-related-games">';foreach($q->get_posts() as $p){if($p->ID!==$id){$html.=GDP()->renderer()->render_card($p->ID);}}return $html.'</div>';}

	private function appearance() {
		return wp_parse_args( get_option( 'gdp_appearance', array() ), array( 'radius'=>10, 'shadow'=>'light', 'container'=>'1200', 'image_ratio'=>'4-3', 'button'=>'filled', 'hover'=>'scale', 'gap'=>20 ) );
	}

	private function wrapper_style( $columns ) {
		$a = $this->appearance();
		$shadows = array( 'none'=>'none', 'light'=>'0 8px 24px rgba(23,36,58,.08)', 'medium'=>'0 12px 32px rgba(23,36,58,.14)', 'strong'=>'0 18px 46px rgba(23,36,58,.22)' );
		$ratios = array( '16-9'=>'16/9', '4-3'=>'4/3', '1-1'=>'1/1', '3-4'=>'3/4' );
		$container = 'full' === $a['container'] ? '100%' : absint( $a['container'] ) . 'px';
		return sprintf( '--gdp-columns:%d;--gdp-radius:%dpx;--gdp-gap:%dpx;--gdp-container:%s;--gdp-shadow:%s;--gdp-image-ratio:%s;', max(1,min(6,absint($columns))), max(0,min(30,absint($a['radius']))), max(0,min(40,absint($a['gap']))), esc_attr($container), esc_attr($shadows[$a['shadow']] ?? $shadows['light']), esc_attr($ratios[$a['image_ratio']] ?? $ratios['4-3']) );
	}

	private function wrapper_classes() {
		$a = $this->appearance();
		return 'gdp-games-wrap gdp-button-' . sanitize_html_class( $a['button'] ) . ' gdp-hover-' . sanitize_html_class( $a['hover'] );
	}

	public function games( $atts ) {
		$raw_atts = is_array( $atts ) ? $atts : array();
		$atts = shortcode_atts( array(
			'type'=>'', 'provider'=>'', 'collection'=>'', 'category'=>'', 'volatility'=>'', 'features'=>'', 'featured'=>'',
			'rtp_min'=>'', 'rtp_max'=>'', 'max_win_min'=>'', 'search'=>'', 'limit'=>8, 'offset'=>0, 'paged'=>1,
			'columns'=>get_option( 'gdp_default_columns', 4 ), 'orderby'=>'date', 'order'=>'DESC', 'filters'=>'false',
		), $atts, 'gdp_games' );
		$atts = GDP_Lite_Hooks::filter( 'shortcode_games_atts', $atts, $raw_atts );

		if ( filter_var( $atts['filters'], FILTER_VALIDATE_BOOLEAN ) ) {
			return $this->render_filterable( $atts );
		}

		$query = new GDP_Lite_Query( $atts );
		$wp_query = $query->get_query();
		GDP_Lite_Frontend_Filters::enqueue_assets();
		ob_start();
		GDP_Lite_Hooks::action( 'before_games_shortcode', $atts, $query );
		echo '<div class="' . esc_attr( $this->wrapper_classes() ) . '" style="' . esc_attr( $this->wrapper_style( $atts['columns'] ) ) . '"><div class="gdp-games-grid">';
		while ( $wp_query->have_posts() ) { $wp_query->the_post(); GDP_Lite_Template_Loader::get_template( 'parts/game-card.php' ); }
		echo '</div></div>';
		wp_reset_postdata();
		GDP_Lite_Hooks::action( 'after_games_shortcode', $atts, $query );
		return GDP_Lite_Hooks::filter( 'games_shortcode_html', ob_get_clean(), $atts, $query );
	}

	public function filters( $atts ) {
		$atts['filters'] = 'true';
		return $this->games( $atts );
	}

	private function render_filterable( $atts ) {
		$args = GDP_Lite_Frontend_Filters::sanitize_query_args( $atts );
		$query = new GDP_Lite_Query( $args );
		GDP_Lite_Frontend_Filters::enqueue_assets();
		ob_start();
		echo '<div class="' . esc_attr( $this->wrapper_classes() ) . ' gdp-filter-container" data-gdp-filter-container style="' . esc_attr( $this->wrapper_style( $atts['columns'] ) ) . '">';
		GDP_Lite_Frontend_Filters::render_filter_form( $args );
		echo '<div class="gdp-filter-status" data-gdp-filter-status aria-live="polite">' . esc_html( sprintf( __( '%d games', 'gdp-lite' ), $query->found_posts() ) ) . '</div>';
		echo '<div class="gdp-filter-results" data-gdp-filter-results tabindex="-1">';
		GDP_Lite_Frontend_Filters::render_results( $query, $args );
		echo '</div></div>';
		return ob_get_clean();
	}
}
