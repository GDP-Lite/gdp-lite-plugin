<?php
/** Builder administration. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Builder_Admin {
	private static $instance = null;
	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register_hooks() { add_action( 'admin_menu', array( $this, 'menu' ), 25 ); add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) ); }
	public function menu() { add_submenu_page( 'gdp-lite', __( 'Builder', 'gdp-lite' ), __( 'Builder', 'gdp-lite' ), 'manage_gdp', 'gdp-lite-builder', array( $this, 'page' ) ); }
	public function assets( $hook ) {
		if ( false === strpos( $hook, 'gdp-lite-builder' ) ) { return; }
		wp_enqueue_style( 'gdp-lite-builder', GDP_LITE_URL . 'assets/css/builder.css', array( 'gdp-lite-admin' ), GDP_LITE_VERSION );
		wp_enqueue_script( 'gdp-lite-builder', GDP_LITE_URL . 'assets/js/builder.js', array(), GDP_LITE_VERSION, true );
	}
	public function page() {
		if ( ! current_user_can( 'manage_gdp' ) ) { wp_die( esc_html__( 'You are not allowed to manage GDP Builder.', 'gdp-lite' ) ); }
		$layout_id = isset( $_GET['layout'] ) ? sanitize_key( wp_unslash( $_GET['layout'] ) ) : 'card';
		if ( 'archive-page' === $layout_id ) { $this->archive_page(); return; }
		$this->layout_page( $layout_id );
	}
	private function tabs( $active ) {
		$tabs = array( 'card' => __( 'Card', 'gdp-lite' ), 'single' => __( 'Single', 'gdp-lite' ), 'archive' => __( 'Archive Item', 'gdp-lite' ), 'archive-page' => __( 'Archive Page', 'gdp-lite' ) );
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $label ) { echo '<a class="nav-tab ' . ( $active === $id ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=gdp-lite-builder&layout=' . $id ) ) . '">' . esc_html( $label ) . '</a>'; }
		echo '</nav>';
	}
	private function header() {
		?><div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'VISUAL BUILDER', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Builder', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Build game cards, single pages, archive items and complete archive pages.', 'gdp-lite' ); ?></p></div></div><?php
	}
	private function layout_page( $layout_id ) {
		if ( ! in_array( $layout_id, array( 'card', 'single', 'archive' ), true ) ) { $layout_id = 'card'; }
		$notice = '';
		if ( isset( $_POST['gdp_builder_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_builder_nonce'] ) ), 'gdp_save_builder' ) ) {
			if ( isset( $_POST['gdp_reset_layout'] ) ) { GDP()->builder()->reset( $layout_id ); $notice = __( 'Layout reset to defaults.', 'gdp-lite' ); }
			else {
				$ids = isset( $_POST['components'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['components'] ) ) : array();
				$components = array(); foreach ( $ids as $id ) { $components[] = array( 'id' => $id, 'settings' => array() ); }
				$result = GDP()->builder()->save( $layout_id, array( 'label' => ucfirst( $layout_id ), 'context' => $layout_id, 'components' => $components ) );
				$notice = is_wp_error( $result ) ? $result->get_error_message() : __( 'Layout saved.', 'gdp-lite' );
			}
		}
		$layout = GDP()->builder()->get( $layout_id ); $available = GDP()->components()->all( $layout_id );
		?><div class="wrap gdp-admin-wrap gdp-builder-admin"><?php $this->header(); if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; $this->tabs( $layout_id ); ?>
		<form method="post" class="gdp-builder-shell"><?php wp_nonce_field( 'gdp_save_builder', 'gdp_builder_nonce' ); ?>
		<section class="gdp-card"><h2><?php esc_html_e( 'Layout Components', 'gdp-lite' ); ?></h2><p><?php esc_html_e( 'Drag components to reorder them. Uncheck a component to remove it.', 'gdp-lite' ); ?></p><ul class="gdp-builder-list" id="gdp-builder-list">
		<?php $active_ids = wp_list_pluck( $layout['components'], 'id' ); $ordered = array_unique( array_merge( $active_ids, array_keys( $available ) ) ); foreach ( $ordered as $component_id ) : if ( ! isset( $available[ $component_id ] ) ) { continue; } $component = $available[ $component_id ]; ?>
		<li draggable="true" class="gdp-builder-item"><span class="dashicons dashicons-move"></span><label><input type="checkbox" name="components[]" value="<?php echo esc_attr( $component_id ); ?>" <?php checked( in_array( $component_id, $active_ids, true ) ); ?>> <strong><?php echo esc_html( $component['label'] ); ?></strong></label><code><?php echo esc_html( $component_id ); ?></code></li><?php endforeach; ?></ul>
		<p><button class="button button-primary"><?php esc_html_e( 'Save Layout', 'gdp-lite' ); ?></button> <button class="button" name="gdp_reset_layout" value="1"><?php esc_html_e( 'Reset Default', 'gdp-lite' ); ?></button></p></section>
		<aside class="gdp-card gdp-builder-preview"><h2><?php esc_html_e( 'Layout Summary', 'gdp-lite' ); ?></h2><dl><div><dt><?php esc_html_e( 'Layout', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( $layout_id ); ?></dd></div><div><dt><?php esc_html_e( 'Components', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( count( $layout['components'] ) ); ?></dd></div></dl></aside></form></div><?php
	}
	private function archive_page() {
		$notice = '';
		if ( isset( $_POST['gdp_archive_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_archive_nonce'] ) ), 'gdp_save_archive_builder' ) ) {
			if ( isset( $_POST['gdp_reset_archive'] ) ) { GDP()->archive_builder()->reset(); $notice = __( 'Archive Builder reset to defaults.', 'gdp-lite' ); }
			else { GDP()->archive_builder()->save( wp_unslash( $_POST ) ); $notice = __( 'Archive Builder settings saved.', 'gdp-lite' ); }
		}
		$s = GDP()->archive_builder()->get();
		$labels = array( 'header' => __( 'Archive Header', 'gdp-lite' ), 'toolbar' => __( 'Search & Sort Toolbar', 'gdp-lite' ), 'grid' => __( 'Game Grid', 'gdp-lite' ), 'pagination' => __( 'Pagination', 'gdp-lite' ), 'empty' => __( 'Empty Result', 'gdp-lite' ) );
		?><div class="wrap gdp-admin-wrap gdp-builder-admin"><?php $this->header(); if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; $this->tabs( 'archive-page' ); ?>
		<form method="post" class="gdp-builder-shell gdp-archive-builder-form"><?php wp_nonce_field( 'gdp_save_archive_builder', 'gdp_archive_nonce' ); ?>
		<div>
		<section class="gdp-card"><h2><?php esc_html_e( 'Archive Sections', 'gdp-lite' ); ?></h2><p><?php esc_html_e( 'Drag sections to control their front-end order.', 'gdp-lite' ); ?></p><ul class="gdp-builder-list" id="gdp-builder-list">
		<?php $ordered = array_unique( array_merge( $s['sections'], array_keys( $labels ) ) ); foreach ( $ordered as $id ) : ?>
		<li draggable="true" class="gdp-builder-item"><span class="dashicons dashicons-move"></span><label><input type="checkbox" name="sections[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $s['sections'], true ) ); ?> <?php disabled( in_array( $id, array( 'grid', 'empty' ), true ) ); ?>> <strong><?php echo esc_html( $labels[ $id ] ); ?></strong><?php if ( in_array( $id, array( 'grid', 'empty' ), true ) ) : ?><input type="hidden" name="sections[]" value="<?php echo esc_attr( $id ); ?>"><?php endif; ?></label><code><?php echo esc_html( $id ); ?></code></li><?php endforeach; ?></ul></section>
		<section class="gdp-card"><h2><?php esc_html_e( 'Grid & Query', 'gdp-lite' ); ?></h2><div class="gdp-builder-fields">
		<label><?php esc_html_e( 'Columns', 'gdp-lite' ); ?><select name="columns"><?php for ( $i=2; $i<=6; $i++ ) : ?><option value="<?php echo esc_attr( $i ); ?>" <?php selected( $s['columns'], $i ); ?>><?php echo esc_html( $i ); ?></option><?php endfor; ?></select></label>
		<label><?php esc_html_e( 'Card style', 'gdp-lite' ); ?><select name="card_style"><option value="classic" <?php selected( $s['card_style'], 'classic' ); ?>><?php esc_html_e( 'Classic', 'gdp-lite' ); ?></option><option value="compact" <?php selected( $s['card_style'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'gdp-lite' ); ?></option><option value="minimal" <?php selected( $s['card_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'gdp-lite' ); ?></option></select></label>
		<label><?php esc_html_e( 'Games per page', 'gdp-lite' ); ?><input type="number" min="1" max="100" name="per_page" value="<?php echo esc_attr( $s['per_page'] ); ?>"></label>
		<label><?php esc_html_e( 'Default order by', 'gdp-lite' ); ?><select name="default_orderby"><option value="date" <?php selected( $s['default_orderby'], 'date' ); ?>><?php esc_html_e( 'Date', 'gdp-lite' ); ?></option><option value="title" <?php selected( $s['default_orderby'], 'title' ); ?>><?php esc_html_e( 'Title', 'gdp-lite' ); ?></option><?php foreach ( GDP()->fields()->all() as $field ) : ?><option value="<?php echo esc_attr( $field->name() ); ?>" <?php selected( $s['default_orderby'], $field->name() ); ?>><?php echo esc_html( $field->get( 'label', $field->name() ) ); ?></option><?php endforeach; ?></select></label>
		<label><?php esc_html_e( 'Default order', 'gdp-lite' ); ?><select name="default_order"><option value="DESC" <?php selected( $s['default_order'], 'DESC' ); ?>>DESC</option><option value="ASC" <?php selected( $s['default_order'], 'ASC' ); ?>>ASC</option></select></label>
		<label><?php esc_html_e( 'Pagination', 'gdp-lite' ); ?><select name="pagination"><option value="numeric" <?php selected( $s['pagination'], 'numeric' ); ?>><?php esc_html_e( 'Numeric', 'gdp-lite' ); ?></option><option value="prev_next" <?php selected( $s['pagination'], 'prev_next' ); ?>><?php esc_html_e( 'Previous / Next', 'gdp-lite' ); ?></option></select></label>
		</div></section>
		<section class="gdp-card"><h2><?php esc_html_e( 'Toolbar & Empty State', 'gdp-lite' ); ?></h2><p><label><input type="checkbox" name="show_description" value="1" <?php checked( $s['show_description'] ); ?>> <?php esc_html_e( 'Show archive description', 'gdp-lite' ); ?></label></p><p><label><input type="checkbox" name="show_search" value="1" <?php checked( $s['show_search'] ); ?>> <?php esc_html_e( 'Show search field', 'gdp-lite' ); ?></label></p><p><label><input type="checkbox" name="show_sort" value="1" <?php checked( $s['show_sort'] ); ?>> <?php esc_html_e( 'Show sorting controls', 'gdp-lite' ); ?></label></p><p><label><input type="checkbox" name="show_per_page" value="1" <?php checked( $s['show_per_page'] ); ?>> <?php esc_html_e( 'Show per-page selector', 'gdp-lite' ); ?></label></p>
		<div class="gdp-builder-fields"><label><?php esc_html_e( 'Empty title', 'gdp-lite' ); ?><input type="text" name="empty_title" value="<?php echo esc_attr( $s['empty_title'] ); ?>"></label><label><?php esc_html_e( 'Empty button', 'gdp-lite' ); ?><input type="text" name="empty_button" value="<?php echo esc_attr( $s['empty_button'] ); ?>"></label><label class="gdp-field-wide"><?php esc_html_e( 'Empty description', 'gdp-lite' ); ?><textarea name="empty_description" rows="3"><?php echo esc_textarea( $s['empty_description'] ); ?></textarea></label></div></section>
		<p><button class="button button-primary"><?php esc_html_e( 'Save Archive Builder', 'gdp-lite' ); ?></button> <button class="button" name="gdp_reset_archive" value="1"><?php esc_html_e( 'Reset Default', 'gdp-lite' ); ?></button></p></div>
		<aside class="gdp-card gdp-builder-preview"><h2><?php esc_html_e( 'Archive Preview Summary', 'gdp-lite' ); ?></h2><dl><div><dt><?php esc_html_e( 'Columns', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( $s['columns'] ); ?></dd></div><div><dt><?php esc_html_e( 'Per page', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( $s['per_page'] ); ?></dd></div><div><dt><?php esc_html_e( 'Card style', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( $s['card_style'] ); ?></dd></div><div><dt><?php esc_html_e( 'Sections', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( count( $s['sections'] ) ); ?></dd></div></dl><p><?php esc_html_e( 'These settings apply to the game archive and all GDP taxonomy archives.', 'gdp-lite' ); ?></p></aside></form></div><?php
	}
}
