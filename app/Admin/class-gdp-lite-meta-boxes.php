<?php
/**
 * Dynamic game edit experience powered by the Field Registry.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Meta_Boxes {
	private static $instance = null;
	/** @var GDP_Lite_Field_Renderer */ private $renderer;

	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	private function __construct() { $this->renderer = new GDP_Lite_Field_Renderer(); }

	public function register_hooks() {
		add_action( 'add_meta_boxes_gdp_game', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_gdp_game', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'gdp_game' !== get_current_screen()->post_type ) { return; }
		wp_enqueue_media();
		wp_enqueue_style( 'gdp-lite-dynamic-fields', GDP_LITE_URL . 'assets/css/admin-fields.css', array(), GDP_LITE_VERSION );
		wp_enqueue_script( 'gdp-lite-dynamic-fields', GDP_LITE_URL . 'assets/js/admin-fields.js', array( 'jquery' ), GDP_LITE_VERSION, true );
	}

	public function add_meta_boxes() {
		remove_meta_box( 'gdp_game_typediv', 'gdp_game', 'side' );
		remove_meta_box( 'tagsdiv-gdp_provider', 'gdp_game', 'side' );
		remove_meta_box( 'gdp_collectiondiv', 'gdp_game', 'side' );
		remove_meta_box( 'gdp_game_categorydiv', 'gdp_game', 'side' );
		add_meta_box( 'gdp-game-details', __( 'Game Data', 'gdp-lite' ), array( $this, 'render' ), 'gdp_game', 'normal', 'high' );
	}

	public function render( $post ) {
		wp_nonce_field( 'gdp_save_game', 'gdp_game_nonce' );
		do_action( 'gdp_before_render_meta_box', $post );
		$fields = GDP()->fields()->all();
		uasort( $fields, static function( $a, $b ) { return (int) $a->get( 'priority', 10 ) <=> (int) $b->get( 'priority', 10 ); } );
		$tabs = array();
		foreach ( $fields as $field ) {
			if ( false === $field->get( 'active', true ) ) { continue; }
			$tab = sanitize_key( $field->get( 'tab', 'gameplay' ) );
			$tabs[ $tab ][] = $field;
		}
		$labels = apply_filters( 'gdp_meta_box_tabs', array(
			'basic' => __( 'Basic', 'gdp-lite' ), 'gameplay' => __( 'Gameplay', 'gdp-lite' ), 'links' => __( 'Links & Display', 'gdp-lite' ), 'features' => __( 'Features', 'gdp-lite' ), 'advanced' => __( 'Advanced', 'gdp-lite' ),
		) );
		?>
		<div class="gdp-meta-tabs" data-gdp-dynamic-fields>
			<nav class="gdp-meta-nav" aria-label="<?php esc_attr_e( 'Game data sections', 'gdp-lite' ); ?>">
				<button type="button" class="is-active" data-tab="basic"><?php esc_html_e( 'Basic', 'gdp-lite' ); ?></button>
				<?php foreach ( $tabs as $tab => $tab_fields ) : ?><button type="button" data-tab="<?php echo esc_attr( $tab ); ?>"><?php echo esc_html( $labels[ $tab ] ?? ucwords( str_replace( '-', ' ', $tab ) ) ); ?></button><?php endforeach; ?>
			</nav>
			<div class="gdp-meta-panel is-active" data-panel="basic">
				<div class="gdp-panel-intro"><h3><?php esc_html_e( 'Game Classification', 'gdp-lite' ); ?></h3><p><?php esc_html_e( 'Assign the game type, provider, category and reusable collections.', 'gdp-lite' ); ?></p></div>
				<div class="gdp-field-grid">
					<?php $this->taxonomy_select( $post->ID, 'gdp_game_type', __( 'Game Type', 'gdp-lite' ) ); ?>
					<?php $this->taxonomy_select( $post->ID, 'gdp_provider', __( 'Provider', 'gdp-lite' ) ); ?>
					<?php $this->taxonomy_select( $post->ID, 'gdp_game_category', __( 'Category', 'gdp-lite' ) ); ?>
					<?php $this->taxonomy_select( $post->ID, 'gdp_collection', __( 'Collection', 'gdp-lite' ), true ); ?>
				</div>
			</div>
			<?php foreach ( $tabs as $tab => $tab_fields ) : ?>
				<div class="gdp-meta-panel" data-panel="<?php echo esc_attr( $tab ); ?>">
					<div class="gdp-field-grid gdp-dynamic-grid">
						<?php foreach ( $tab_fields as $field ) :
							$width = absint( $field->get( 'width', 50 ) );
							if ( ! in_array( $width, array( 25, 33, 50, 100 ), true ) ) { $width = 50; }
							$value = GDP()->fields()->get_value( $post->ID, $field->name(), $field->get( 'default' ) );
							$show_if = $field->get( 'show_if', array() );
							echo '<div class="gdp-field-wrap gdp-width-' . esc_attr( $width ) . '" data-show-if="' . esc_attr( wp_json_encode( $show_if ) ) . '">';
							$this->renderer->render_admin( $field, $value, $post->ID );
							echo '</div>';
						endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		do_action( 'gdp_after_render_meta_box', $post );
	}

	private function taxonomy_select( $post_id, $taxonomy, $label, $multiple = false ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		$assigned = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
		$name = 'gdp_tax_' . $taxonomy . ( $multiple ? '[]' : '' );
		echo '<label class="gdp-field"><span>' . esc_html( $label ) . '</span><select name="' . esc_attr( $name ) . '"' . ( $multiple ? ' multiple size="5"' : '' ) . '><option value="">' . esc_html__( 'Select', 'gdp-lite' ) . '</option>';
		if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( in_array( $term->term_id, $assigned, true ), true, false ) . '>' . esc_html( $term->name ) . '</option>'; } }
		echo '</select><small><a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=gdp_game' ) ) . '">' . esc_html__( 'Manage options', 'gdp-lite' ) . '</a></small></label>';
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['gdp_game_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_game_nonce'] ) ), 'gdp_save_game' ) ) { return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! $post || 'gdp_game' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) { return; }
		do_action( 'gdp_before_save_meta_box', $post_id, $post );

		$submitted = isset( $_POST['gdp_fields'] ) && is_array( $_POST['gdp_fields'] ) ? wp_unslash( $_POST['gdp_fields'] ) : array();
		$errors = array();
		foreach ( GDP()->fields()->all() as $field ) {
			if ( false === $field->get( 'active', true ) ) { continue; }
			$name = $field->name();
			$value = 'switch' === $field->type() ? ( isset( $submitted[ $name ] ) ? 1 : 0 ) : ( $submitted[ $name ] ?? '' );
			$result = GDP()->fields()->update_value( $post_id, $name, $value );
			if ( is_wp_error( $result ) ) { $errors[ $name ] = $result->get_error_message(); }
		}

		foreach ( array( 'gdp_game_type', 'gdp_provider', 'gdp_game_category', 'gdp_collection' ) as $taxonomy ) {
			$name = 'gdp_tax_' . $taxonomy;
			$raw = isset( $_POST[ $name ] ) ? (array) wp_unslash( $_POST[ $name ] ) : array();
			$ids = array_values( array_filter( array_map( 'absint', $raw ) ) );
			wp_set_object_terms( $post_id, $ids, $taxonomy, false );
		}

		$max_win = GDP()->fields()->get_value( $post_id, 'max_win', '' );
		preg_match( '/[0-9]+(?:\.[0-9]+)?/', (string) $max_win, $matches );
		update_post_meta( $post_id, '_gdp_max_win_value', isset( $matches[0] ) ? (float) $matches[0] : 0 );
		if ( $errors ) { set_transient( 'gdp_field_errors_' . get_current_user_id(), $errors, 60 ); }
		do_action( 'gdp_after_save_meta_box', $post_id, $post, $errors );
	}
}
