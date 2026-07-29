<?php
/**
 * Archive Builder settings, query integration, and rendering.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Archive_Builder {
	private static $instance = null;
	const OPTION = 'gdp_archive_builder';

	public static function instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	public function register_hooks() {
		add_action( 'pre_get_posts', array( $this, 'prepare_query' ) );
	}

	public function defaults() {
		return array(
			'sections'          => array( 'header', 'toolbar', 'grid', 'pagination', 'empty' ),
			'columns'           => 4,
			'card_style'        => 'classic',
			'per_page'          => 12,
			'default_orderby'   => 'date',
			'default_order'     => 'DESC',
			'show_description'  => 1,
			'show_search'       => 1,
			'show_sort'         => 1,
			'show_per_page'     => 1,
			'pagination'        => 'numeric',
			'empty_title'       => __( 'No games found.', 'gdp-lite' ),
			'empty_description' => __( 'Try changing the search or archive filters.', 'gdp-lite' ),
			'empty_button'      => __( 'View all games', 'gdp-lite' ),
		);
	}

	public function get() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), $this->defaults() );
	}

	public function save( $input ) {
		$settings = $this->sanitize( $input );
		update_option( self::OPTION, $settings, false );
		update_option( 'gdp_builder_version', (int) get_option( 'gdp_builder_version', 1 ) + 1, false );
		if ( GDP()->cache() ) { GDP()->cache()->flush(); }
		do_action( 'gdp_archive_builder_saved', $settings );
		return $settings;
	}

	public function reset() {
		delete_option( self::OPTION );
		do_action( 'gdp_archive_builder_reset' );
		return $this->get();
	}

	public function sanitize( $input ) {
		$defaults = $this->defaults();
		$allowed_sections = array( 'header', 'toolbar', 'grid', 'pagination', 'empty' );
		$sections = array_values( array_unique( array_intersect( array_map( 'sanitize_key', (array) ( $input['sections'] ?? array() ) ), $allowed_sections ) ) );
		if ( ! in_array( 'grid', $sections, true ) ) { $sections[] = 'grid'; }
		if ( ! in_array( 'empty', $sections, true ) ) { $sections[] = 'empty'; }

		$orderby = sanitize_key( $input['default_orderby'] ?? $defaults['default_orderby'] );
		$allowed_orderby = array_merge( array( 'date', 'title', 'menu_order', 'rand' ), array_keys( GDP()->fields()->all() ) );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) { $orderby = 'date'; }

		return array(
			'sections'          => $sections,
			'columns'           => max( 2, min( 6, absint( $input['columns'] ?? 4 ) ) ),
			'card_style'        => in_array( sanitize_key( $input['card_style'] ?? '' ), array( 'classic', 'compact', 'minimal' ), true ) ? sanitize_key( $input['card_style'] ) : 'classic',
			'per_page'          => max( 1, min( 100, absint( $input['per_page'] ?? 12 ) ) ),
			'default_orderby'   => $orderby,
			'default_order'     => 'ASC' === strtoupper( sanitize_text_field( $input['default_order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC',
			'show_description'  => empty( $input['show_description'] ) ? 0 : 1,
			'show_search'       => empty( $input['show_search'] ) ? 0 : 1,
			'show_sort'         => empty( $input['show_sort'] ) ? 0 : 1,
			'show_per_page'     => empty( $input['show_per_page'] ) ? 0 : 1,
			'pagination'        => in_array( sanitize_key( $input['pagination'] ?? '' ), array( 'numeric', 'prev_next' ), true ) ? sanitize_key( $input['pagination'] ) : 'numeric',
			'empty_title'       => sanitize_text_field( $input['empty_title'] ?? $defaults['empty_title'] ),
			'empty_description' => sanitize_textarea_field( $input['empty_description'] ?? $defaults['empty_description'] ),
			'empty_button'      => sanitize_text_field( $input['empty_button'] ?? $defaults['empty_button'] ),
		);
	}

	public function is_archive( $query = null ) {
		if ( $query instanceof WP_Query ) {
			return $query->is_post_type_archive( 'gdp_game' ) || $query->is_tax( array( 'gdp_game_type', 'gdp_provider', 'gdp_collection', 'gdp_game_category' ) );
		}
		return is_post_type_archive( 'gdp_game' ) || is_tax( array( 'gdp_game_type', 'gdp_provider', 'gdp_collection', 'gdp_game_category' ) );
	}

	public function prepare_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $this->is_archive( $query ) ) { return; }
		$settings = $this->get();
		$per_page = isset( $_GET['gdp_per_page'] ) ? absint( wp_unslash( $_GET['gdp_per_page'] ) ) : (int) $settings['per_page'];
		$query->set( 'posts_per_page', max( 1, min( 100, $per_page ) ) );

		if ( ! empty( $_GET['gdp_search'] ) ) {
			$query->set( 's', sanitize_text_field( wp_unslash( $_GET['gdp_search'] ) ) );
		}

		$orderby = isset( $_GET['gdp_orderby'] ) ? sanitize_key( wp_unslash( $_GET['gdp_orderby'] ) ) : $settings['default_orderby'];
		$order   = isset( $_GET['gdp_order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['gdp_order'] ) ) ) : $settings['default_order'];
		$order   = 'ASC' === $order ? 'ASC' : 'DESC';
		$field   = GDP()->fields()->get( $orderby );
		if ( $field ) {
			$query->set( 'meta_key', $field->meta_key() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', in_array( $field->type(), array( 'number', 'decimal', 'switch' ), true ) ? 'meta_value_num' : 'meta_value' );
		} elseif ( in_array( $orderby, array( 'date', 'title', 'menu_order', 'rand' ), true ) ) {
			$query->set( 'orderby', $orderby );
		}
		$query->set( 'order', $order );
	}

	public function render( $title = '', $description = '' ) {
		$settings = $this->get();
		$title = $title ?: post_type_archive_title( '', false );
		do_action( 'gdp_before_archive_builder', $settings, $title, $description );
		?>
		<main class="gdp-template-page gdp-archive-builder-page">
			<div class="gdp-games-wrap <?php echo esc_attr( GDP_Lite_Template_Loader::appearance_classes() ); ?>">
				<?php foreach ( $settings['sections'] as $section ) { $this->render_section( $section, $settings, $title, $description ); } ?>
			</div>
		</main>
		<?php
		do_action( 'gdp_after_archive_builder', $settings, $title, $description );
	}

	private function render_section( $section, $settings, $title, $description ) {
		switch ( $section ) {
			case 'header':
				do_action( 'gdp_archive_before_header', $settings );
				echo '<header class="gdp-archive-header"><h1>' . esc_html( $title ) . '</h1>';
				if ( $settings['show_description'] && $description ) { echo '<div class="gdp-archive-description">' . wp_kses_post( $description ) . '</div>'; }
				echo '</header>';
				do_action( 'gdp_archive_after_header', $settings );
				break;
			case 'toolbar':
				$this->render_toolbar( $settings );
				break;
			case 'grid':
				if ( have_posts() ) { $this->render_grid( $settings ); }
				break;
			case 'pagination':
				if ( have_posts() ) { $this->render_pagination( $settings ); }
				break;
			case 'empty':
				if ( ! have_posts() ) { $this->render_empty( $settings ); }
				break;
		}
	}

	private function render_toolbar( $settings ) {
		if ( ! $settings['show_search'] && ! $settings['show_sort'] && ! $settings['show_per_page'] ) { return; }
		do_action( 'gdp_archive_before_toolbar', $settings );
		$current_orderby = isset( $_GET['gdp_orderby'] ) ? sanitize_key( wp_unslash( $_GET['gdp_orderby'] ) ) : $settings['default_orderby'];
		$current_order = isset( $_GET['gdp_order'] ) && 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['gdp_order'] ) ) ) ? 'ASC' : $settings['default_order'];
		?>
		<form class="gdp-archive-toolbar" method="get" action="">
			<?php if ( $settings['show_search'] ) : ?><label><span class="screen-reader-text"><?php esc_html_e( 'Search games', 'gdp-lite' ); ?></span><input type="search" name="gdp_search" value="<?php echo isset( $_GET['gdp_search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['gdp_search'] ) ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Search games…', 'gdp-lite' ); ?>"></label><?php endif; ?>
			<?php if ( $settings['show_sort'] ) : ?>
			<label><span><?php esc_html_e( 'Sort', 'gdp-lite' ); ?></span><select name="gdp_orderby">
				<option value="date" <?php selected( $current_orderby, 'date' ); ?>><?php esc_html_e( 'Newest', 'gdp-lite' ); ?></option>
				<option value="title" <?php selected( $current_orderby, 'title' ); ?>><?php esc_html_e( 'Title', 'gdp-lite' ); ?></option>
				<?php foreach ( GDP()->fields()->all() as $field ) : if ( ! $field->get( 'filterable', false ) ) { continue; } ?><option value="<?php echo esc_attr( $field->name() ); ?>" <?php selected( $current_orderby, $field->name() ); ?>><?php echo esc_html( $field->get( 'label', $field->name() ) ); ?></option><?php endforeach; ?>
			</select></label>
			<label><span class="screen-reader-text"><?php esc_html_e( 'Order', 'gdp-lite' ); ?></span><select name="gdp_order"><option value="DESC" <?php selected( $current_order, 'DESC' ); ?>><?php esc_html_e( 'Descending', 'gdp-lite' ); ?></option><option value="ASC" <?php selected( $current_order, 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'gdp-lite' ); ?></option></select></label>
			<?php endif; ?>
			<?php if ( $settings['show_per_page'] ) : ?><label><span><?php esc_html_e( 'Per page', 'gdp-lite' ); ?></span><select name="gdp_per_page"><?php foreach ( array( 12, 24, 36, 48 ) as $count ) : ?><option value="<?php echo esc_attr( $count ); ?>" <?php selected( isset( $_GET['gdp_per_page'] ) ? absint( $_GET['gdp_per_page'] ) : $settings['per_page'], $count ); ?>><?php echo esc_html( $count ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
			<button class="gdp-game-button" type="submit"><?php esc_html_e( 'Apply', 'gdp-lite' ); ?></button>
		</form>
		<?php
		do_action( 'gdp_archive_after_toolbar', $settings );
	}

	private function render_grid( $settings ) {
		do_action( 'gdp_archive_before_grid', $settings );
		echo '<div class="gdp-games-grid gdp-card-style-' . esc_attr( $settings['card_style'] ) . '" style="--gdp-columns:' . esc_attr( $settings['columns'] ) . '">';
		while ( have_posts() ) { the_post(); echo GDP()->builder()->render( 'card', get_the_ID(), array( 'archive' => true ) ); }
		echo '</div>';
		do_action( 'gdp_archive_after_grid', $settings );
	}

	private function render_pagination( $settings ) {
		do_action( 'gdp_archive_before_pagination', $settings );
		if ( 'prev_next' === $settings['pagination'] ) {
			echo '<nav class="gdp-pagination gdp-pagination-prev-next">';
			previous_posts_link( esc_html__( 'Previous', 'gdp-lite' ) );
			next_posts_link( esc_html__( 'Next', 'gdp-lite' ) );
			echo '</nav>';
		} else {
			the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'Previous', 'gdp-lite' ), 'next_text' => __( 'Next', 'gdp-lite' ) ) );
		}
		do_action( 'gdp_archive_after_pagination', $settings );
	}

	private function render_empty( $settings ) {
		do_action( 'gdp_archive_before_empty', $settings );
		echo '<div class="gdp-empty gdp-builder-empty"><h2>' . esc_html( $settings['empty_title'] ) . '</h2><p>' . esc_html( $settings['empty_description'] ) . '</p>';
		if ( $settings['empty_button'] ) { echo '<a class="gdp-game-button" href="' . esc_url( get_post_type_archive_link( 'gdp_game' ) ) . '">' . esc_html( $settings['empty_button'] ) . '</a>'; }
		echo '</div>';
		do_action( 'gdp_archive_after_empty', $settings );
	}
}
