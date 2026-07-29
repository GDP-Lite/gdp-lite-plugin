<?php
/**
 * AJAX front-end filtering for game grids.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Frontend_Filters {
	/** @var GDP_Lite_Frontend_Filters|null */
	private static $instance = null;

	/** @return GDP_Lite_Frontend_Filters */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** @return void */
	public function register_hooks() {
		add_action( 'wp_ajax_gdp_filter_games', array( $this, 'ajax_filter_games' ) );
		add_action( 'wp_ajax_nopriv_gdp_filter_games', array( $this, 'ajax_filter_games' ) );
	}

	/** @return void */
	public static function enqueue_assets() {
		wp_enqueue_style( 'gdp-lite-public', GDP_LITE_URL . 'assets/css/public.css', array(), GDP_LITE_VERSION );
		wp_enqueue_script( 'gdp-lite-public', GDP_LITE_URL . 'assets/js/public.js', array(), GDP_LITE_VERSION, true );
		wp_localize_script(
			'gdp-lite-public',
			'gdpLiteFilters',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gdp_filter_games' ),
				'loading' => __( 'Loading games…', 'gdp-lite' ),
				'error'   => __( 'Games could not be loaded. Please try again.', 'gdp-lite' ),
			)
		);
	}

	/** @return void */
	public function ajax_filter_games() {
		check_ajax_referer( 'gdp_filter_games', 'nonce' );

		$raw = isset( $_POST['query'] ) && is_array( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : array();
		$args = self::sanitize_query_args( $raw );
		$query = new GDP_Lite_Query( $args );

		ob_start();
		self::render_results( $query, $args );
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'       => $html,
				'foundPosts' => $query->found_posts(),
				'maxPages'   => $query->max_num_pages(),
				'page'       => (int) $args['paged'],
			)
		);
	}

	/**
	 * @param array<string,mixed> $raw Raw request values.
	 * @return array<string,mixed>
	 */
	public static function sanitize_query_args( $raw ) {
		$allowed_orderby = array( 'date', 'modified', 'title', 'rtp', 'max_win', 'popular', 'rand' );
		$orderby = isset( $raw['orderby'] ) ? sanitize_key( $raw['orderby'] ) : 'date';
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'date';
		}

		return array(
			'type'        => isset( $raw['type'] ) ? sanitize_title( $raw['type'] ) : '',
			'provider'    => isset( $raw['provider'] ) ? sanitize_title( $raw['provider'] ) : '',
			'collection'  => isset( $raw['collection'] ) ? sanitize_title( $raw['collection'] ) : '',
			'category'    => isset( $raw['category'] ) ? sanitize_title( $raw['category'] ) : '',
			'volatility'  => isset( $raw['volatility'] ) ? sanitize_key( $raw['volatility'] ) : '',
			'search'      => isset( $raw['search'] ) ? sanitize_text_field( $raw['search'] ) : '',
			'featured'    => isset( $raw['featured'] ) ? sanitize_text_field( $raw['featured'] ) : '',
			'rtp_min'     => isset( $raw['rtp_min'] ) ? sanitize_text_field( $raw['rtp_min'] ) : '',
			'rtp_max'     => isset( $raw['rtp_max'] ) ? sanitize_text_field( $raw['rtp_max'] ) : '',
			'max_win_min' => isset( $raw['max_win_min'] ) ? sanitize_text_field( $raw['max_win_min'] ) : '',
			'orderby'     => $orderby,
			'order'       => isset( $raw['order'] ) && 'ASC' === strtoupper( sanitize_text_field( $raw['order'] ) ) ? 'ASC' : 'DESC',
			'limit'       => isset( $raw['limit'] ) ? max( 1, min( 48, absint( $raw['limit'] ) ) ) : 12,
			'paged'       => isset( $raw['paged'] ) ? max( 1, absint( $raw['paged'] ) ) : 1,
		);
	}

	/**
	 * @param GDP_Lite_Query       $query Query object.
	 * @param array<string,mixed> $args Query arguments.
	 * @return void
	 */
	public static function render_results( $query, $args ) {
		$wp_query = $query->get_query();
		if ( $wp_query->have_posts() ) {
			echo '<div class="gdp-games-grid">';
			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				GDP_Lite_Template_Loader::get_template( 'parts/game-card.php' );
			}
			echo '</div>';
			self::render_ajax_pagination( $query->max_num_pages(), (int) $args['paged'] );
		} else {
			GDP_Lite_Template_Loader::get_template( 'parts/empty.php' );
		}
		wp_reset_postdata();
	}

	/** @param int $max_pages Maximum pages. @param int $current Current page. @return void */
	private static function render_ajax_pagination( $max_pages, $current ) {
		if ( $max_pages <= 1 ) {
			return;
		}
		echo '<nav class="gdp-ajax-pagination" aria-label="' . esc_attr__( 'Games pagination', 'gdp-lite' ) . '">';
		if ( $current > 1 ) {
			echo '<button type="button" data-gdp-page="' . esc_attr( $current - 1 ) . '">' . esc_html__( 'Previous', 'gdp-lite' ) . '</button>';
		}
		echo '<span>' . esc_html( sprintf( __( 'Page %1$d of %2$d', 'gdp-lite' ), $current, $max_pages ) ) . '</span>';
		if ( $current < $max_pages ) {
			echo '<button type="button" data-gdp-page="' . esc_attr( $current + 1 ) . '">' . esc_html__( 'Next', 'gdp-lite' ) . '</button>';
		}
		echo '</nav>';
	}

	/** @param array<string,mixed> $args Initial query args. @return void */
	public static function render_filter_form( $args ) {
		$types     = get_terms( array( 'taxonomy' => 'gdp_game_type', 'hide_empty' => true ) );
		$providers = get_terms( array( 'taxonomy' => 'gdp_provider', 'hide_empty' => true ) );
		?>
		<form class="gdp-filter-form" data-gdp-filter-form>
			<div class="gdp-filter-field gdp-filter-search">
				<label for="gdp-filter-search-<?php echo esc_attr( wp_unique_id() ); ?>"><?php esc_html_e( 'Search games', 'gdp-lite' ); ?></label>
				<input type="search" name="search" placeholder="<?php esc_attr_e( 'Enter a game name', 'gdp-lite' ); ?>" value="<?php echo esc_attr( $args['search'] ?? '' ); ?>">
			</div>
			<div class="gdp-filter-field">
				<label><?php esc_html_e( 'Game Type', 'gdp-lite' ); ?></label>
				<select name="type"><option value=""><?php esc_html_e( 'All Types', 'gdp-lite' ); ?></option>
				<?php if ( ! is_wp_error( $types ) ) { foreach ( $types as $term ) { printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $term->slug ), selected( $args['type'] ?? '', $term->slug, false ), esc_html( $term->name ) ); } } ?>
				</select>
			</div>
			<div class="gdp-filter-field">
				<label><?php esc_html_e( 'Provider', 'gdp-lite' ); ?></label>
				<select name="provider"><option value=""><?php esc_html_e( 'All Providers', 'gdp-lite' ); ?></option>
				<?php if ( ! is_wp_error( $providers ) ) { foreach ( $providers as $term ) { printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $term->slug ), selected( $args['provider'] ?? '', $term->slug, false ), esc_html( $term->name ) ); } } ?>
				</select>
			</div>
			<div class="gdp-filter-field">
				<label><?php esc_html_e( 'Volatility', 'gdp-lite' ); ?></label>
				<select name="volatility">
					<option value=""><?php esc_html_e( 'All Volatility', 'gdp-lite' ); ?></option>
					<option value="low"><?php esc_html_e( 'Low', 'gdp-lite' ); ?></option>
					<option value="medium"><?php esc_html_e( 'Medium', 'gdp-lite' ); ?></option>
					<option value="high"><?php esc_html_e( 'High', 'gdp-lite' ); ?></option>
				</select>
			</div>
			<div class="gdp-filter-field">
				<label><?php esc_html_e( 'Sort By', 'gdp-lite' ); ?></label>
				<select name="orderby">
					<option value="date"><?php esc_html_e( 'Newest', 'gdp-lite' ); ?></option>
					<option value="title"><?php esc_html_e( 'Name', 'gdp-lite' ); ?></option>
					<option value="rtp"><?php esc_html_e( 'RTP', 'gdp-lite' ); ?></option>
					<option value="max_win"><?php esc_html_e( 'Max Win', 'gdp-lite' ); ?></option>
					<option value="popular"><?php esc_html_e( 'Popular', 'gdp-lite' ); ?></option>
				</select>
			</div>
			<input type="hidden" name="order" value="DESC">
			<input type="hidden" name="limit" value="<?php echo esc_attr( $args['limit'] ?? 12 ); ?>">
			<input type="hidden" name="paged" value="1">
			<button class="gdp-filter-submit" type="submit"><?php esc_html_e( 'Apply Filters', 'gdp-lite' ); ?></button>
			<button class="gdp-filter-reset" type="reset"><?php esc_html_e( 'Reset', 'gdp-lite' ); ?></button>
		</form>
		<?php
	}
}
