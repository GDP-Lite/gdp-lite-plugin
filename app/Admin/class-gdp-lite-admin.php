<?php
/**
 * Professional admin UI.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Admin {
	/** @var GDP_Lite_Admin|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'in_admin_header', array( $this, 'remove_third_party_notices' ), 1 );
		add_filter( 'manage_gdp_game_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_gdp_game_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
	}

	public function menu() {
		add_menu_page( 'GDP Lite', 'GDP Lite', 'edit_games', 'gdp-lite', array( $this, 'dashboard' ), 'dashicons-games', 26 );
		add_submenu_page( 'gdp-lite', __( 'Dashboard', 'gdp-lite' ), __( 'Dashboard', 'gdp-lite' ), 'edit_games', 'gdp-lite', array( $this, 'dashboard' ) );
		add_submenu_page( 'gdp-lite', __( 'All Games', 'gdp-lite' ), __( 'All Games', 'gdp-lite' ), 'edit_games', 'edit.php?post_type=gdp_game' );
		add_submenu_page( 'gdp-lite', __( 'Add New Game', 'gdp-lite' ), __( 'Add New', 'gdp-lite' ), 'edit_games', 'post-new.php?post_type=gdp_game' );
		add_submenu_page( 'gdp-lite', __( 'Game Types', 'gdp-lite' ), __( 'Game Types', 'gdp-lite' ), 'manage_categories', 'edit-tags.php?taxonomy=gdp_game_type&post_type=gdp_game' );
		add_submenu_page( 'gdp-lite', __( 'Providers', 'gdp-lite' ), __( 'Providers', 'gdp-lite' ), 'manage_categories', 'edit-tags.php?taxonomy=gdp_provider&post_type=gdp_game' );
		add_submenu_page( 'gdp-lite', __( 'Collections', 'gdp-lite' ), __( 'Collections', 'gdp-lite' ), 'manage_categories', 'edit-tags.php?taxonomy=gdp_collection&post_type=gdp_game' );
		add_submenu_page( 'gdp-lite', __( 'Categories', 'gdp-lite' ), __( 'Categories', 'gdp-lite' ), 'manage_categories', 'edit-tags.php?taxonomy=gdp_game_category&post_type=gdp_game' );
		add_submenu_page( 'gdp-lite', __( 'Import / Export', 'gdp-lite' ), __( 'Import / Export', 'gdp-lite' ), 'import_games', 'gdp-lite-import', array( 'GDP_Lite_Importer', 'render_page' ) );
		add_submenu_page( 'gdp-lite', __( 'Appearance', 'gdp-lite' ), __( 'Appearance', 'gdp-lite' ), 'manage_options', 'gdp-lite-appearance', array( $this, 'appearance' ) );
		add_submenu_page( 'gdp-lite', __( 'Performance', 'gdp-lite' ), __( 'Performance', 'gdp-lite' ), 'manage_options', 'gdp-lite-performance', array( $this, 'performance' ) );
		add_submenu_page( 'gdp-lite', __( 'Developer', 'gdp-lite' ), __( 'Developer', 'gdp-lite' ), 'manage_gdp', 'gdp-lite-developer', array( $this, 'developer' ) );
		add_submenu_page( 'gdp-lite', __( 'Health Check', 'gdp-lite' ), __( 'Health Check', 'gdp-lite' ), 'manage_gdp', 'gdp-lite-health', array( $this, 'health' ) );
		add_submenu_page( 'gdp-lite', __( 'Permalinks', 'gdp-lite' ), __( 'Permalinks', 'gdp-lite' ), 'manage_options', 'gdp-lite-permalinks', array( $this, 'permalinks' ) );
		add_submenu_page( 'gdp-lite', __( 'Settings', 'gdp-lite' ), __( 'Settings', 'gdp-lite' ), 'manage_options', 'gdp-lite-settings', array( $this, 'settings' ) );
	}

	public function remove_third_party_notices() {
		if ( ! $this->is_gdp_custom_page() ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	private function is_gdp_custom_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		return 'gdp-lite' === $page || 0 === strpos( $page, 'gdp-lite-' );
	}

	public function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ( 'gdp_game' !== $screen->post_type && false === strpos( $hook, 'gdp-lite' ) ) ) {
			return;
		}
		wp_enqueue_style( 'gdp-lite-admin', GDP_LITE_URL . 'assets/css/admin.css', array(), GDP_LITE_VERSION );
		wp_enqueue_script( 'gdp-lite-admin', GDP_LITE_URL . 'assets/js/admin.js', array(), GDP_LITE_VERSION, true );
		wp_localize_script( 'gdp-lite-admin', 'gdpLiteAdmin', array( 'copied' => __( 'Copied', 'gdp-lite' ) ) );
	}

	public function dashboard() {
		$counts      = wp_count_posts( 'gdp_game' );
		$total       = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$total      += isset( $counts->draft ) ? (int) $counts->draft : 0;
		$providers   = wp_count_terms( array( 'taxonomy' => 'gdp_provider', 'hide_empty' => false ) );
		$collections = wp_count_terms( array( 'taxonomy' => 'gdp_collection', 'hide_empty' => false ) );
		$types       = wp_count_terms( array( 'taxonomy' => 'gdp_game_type', 'hide_empty' => false ) );
		$recent      = get_posts( array( 'post_type' => 'gdp_game', 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => 6, 'orderby' => 'modified', 'order' => 'DESC' ) );
		?>
		<div class="wrap gdp-admin-wrap">
			<header class="gdp-topbar">
				<div><span class="gdp-eyebrow"><?php esc_html_e( 'PROFESSIONAL ADMIN UI', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'GDP Lite', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Lightweight gaming manager for WordPress.', 'gdp-lite' ); ?></p></div>
				<div class="gdp-topbar-actions"><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=gdp-lite-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'gdp-lite' ); ?></a><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gdp_game' ) ); ?>"><?php esc_html_e( 'Add New Game', 'gdp-lite' ); ?></a></div>
			</header>

			<section class="gdp-stat-grid">
				<?php $this->stat_card( $total, __( 'Games', 'gdp-lite' ), 'dashicons-games', admin_url( 'edit.php?post_type=gdp_game' ) ); ?>
				<?php $this->stat_card( is_wp_error( $providers ) ? 0 : $providers, __( 'Providers', 'gdp-lite' ), 'dashicons-building', admin_url( 'edit-tags.php?taxonomy=gdp_provider&post_type=gdp_game' ) ); ?>
				<?php $this->stat_card( is_wp_error( $collections ) ? 0 : $collections, __( 'Collections', 'gdp-lite' ), 'dashicons-screenoptions', admin_url( 'edit-tags.php?taxonomy=gdp_collection&post_type=gdp_game' ) ); ?>
				<?php $this->stat_card( is_wp_error( $types ) ? 0 : $types, __( 'Game Types', 'gdp-lite' ), 'dashicons-grid-view', admin_url( 'edit-tags.php?taxonomy=gdp_game_type&post_type=gdp_game' ) ); ?>
				<?php $this->stat_card( GDP_LITE_VERSION, __( 'Version', 'gdp-lite' ), 'dashicons-info-outline', admin_url( 'plugins.php' ) ); ?>
			</section>

			<?php if ( 0 === $total ) : ?>
			<section class="gdp-card gdp-getting-started">
				<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'Getting Started', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Create a provider, add a game, then display it with the shortcode.', 'gdp-lite' ); ?></h2></div></div>
				<div class="gdp-steps">
					<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=gdp_provider&post_type=gdp_game' ) ); ?>"><b>1</b><span><?php esc_html_e( 'Create Provider', 'gdp-lite' ); ?></span></a>
					<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=gdp_collection&post_type=gdp_game' ) ); ?>"><b>2</b><span><?php esc_html_e( 'Create Collection', 'gdp-lite' ); ?></span></a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gdp_game' ) ); ?>"><b>3</b><span><?php esc_html_e( 'Add Game', 'gdp-lite' ); ?></span></a>
					<a href="#gdp-shortcodes"><b>4</b><span><?php esc_html_e( 'Display Games', 'gdp-lite' ); ?></span></a>
				</div>
			</section>
			<?php endif; ?>

			<div class="gdp-dashboard-grid">
				<section class="gdp-card gdp-card-wide">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'WORKFLOW', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Quick Actions', 'gdp-lite' ); ?></h2></div></div>
					<div class="gdp-action-grid">
						<?php $this->action_card( 'dashicons-plus-alt2', __( 'Add Game', 'gdp-lite' ), __( 'Create a new game', 'gdp-lite' ), admin_url( 'post-new.php?post_type=gdp_game' ) ); ?>
						<?php $this->action_card( 'dashicons-upload', __( 'Import / Export', 'gdp-lite' ), __( 'Move JSON or CSV game data', 'gdp-lite' ), admin_url( 'admin.php?page=gdp-lite-import' ) ); ?>
						<?php $this->action_card( 'dashicons-building', __( 'Providers', 'gdp-lite' ), __( 'Manage game providers', 'gdp-lite' ), admin_url( 'edit-tags.php?taxonomy=gdp_provider&post_type=gdp_game' ) ); ?>
						<?php $this->action_card( 'dashicons-screenoptions', __( 'Collections', 'gdp-lite' ), __( 'Build reusable groups', 'gdp-lite' ), admin_url( 'edit-tags.php?taxonomy=gdp_collection&post_type=gdp_game' ) ); ?>
						<?php $this->action_card( 'dashicons-admin-generic', __( 'Settings', 'gdp-lite' ), __( 'Configure default display', 'gdp-lite' ), admin_url( 'admin.php?page=gdp-lite-settings' ) ); ?>
					</div>
				</section>

				<section id="gdp-shortcodes" class="gdp-card">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'DISPLAY', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Shortcodes', 'gdp-lite' ); ?></h2></div></div>
					<div class="gdp-shortcode-list">
						<?php $this->shortcode_card( __( 'Slots', 'gdp-lite' ), '[gdp_games type="slots" limit="8" columns="4"]' ); ?>
						<?php $this->shortcode_card( __( 'Provider', 'gdp-lite' ), '[gdp_games provider="jili" limit="12"]' ); ?>
						<?php $this->shortcode_card( __( 'Collection', 'gdp-lite' ), '[gdp_games collection="popular" limit="8"]' ); ?>
					</div>
				</section>

				<section class="gdp-card gdp-card-wide">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'CONTENT', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Recently Updated Games', 'gdp-lite' ); ?></h2></div><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gdp_game' ) ); ?>"><?php esc_html_e( 'View all', 'gdp-lite' ); ?></a></div>
					<?php $this->recent_games( $recent ); ?>
				</section>

				<section class="gdp-card">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'SYSTEM', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Environment', 'gdp-lite' ); ?></h2></div></div>
					<dl class="gdp-system-list"><div><dt>GDP Lite</dt><dd><?php echo esc_html( GDP_LITE_VERSION ); ?></dd></div><div><dt>WordPress</dt><dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd></div><div><dt>PHP</dt><dd><?php echo esc_html( PHP_VERSION ); ?></dd></div><div><dt><?php esc_html_e( 'Theme', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></dd></div></dl>
				</section>
			</div>
		</div>
		<?php
	}

	private function stat_card( $value, $label, $icon, $url ) {
		echo '<a class="gdp-stat" href="' . esc_url( $url ) . '"><span class="dashicons ' . esc_attr( $icon ) . '"></span><strong>' . esc_html( $value ) . '</strong><span>' . esc_html( $label ) . '</span></a>';
	}

	private function action_card( $icon, $title, $description, $url ) {
		echo '<a href="' . esc_url( $url ) . '"><span class="dashicons ' . esc_attr( $icon ) . '"></span><strong>' . esc_html( $title ) . '</strong><small>' . esc_html( $description ) . '</small></a>';
	}

	private function shortcode_card( $title, $shortcode ) {
		echo '<div class="gdp-shortcode"><div class="gdp-shortcode-head"><strong>' . esc_html( $title ) . '</strong><button type="button" class="button gdp-copy" data-copy="' . esc_attr( $shortcode ) . '"><span class="dashicons dashicons-clipboard"></span><span class="gdp-copy-label">' . esc_html__( 'Copy', 'gdp-lite' ) . '</span></button></div><code>' . esc_html( $shortcode ) . '</code></div>';
	}

	private function recent_games( $recent ) {
		if ( ! $recent ) {
			echo '<div class="gdp-empty-state"><span class="dashicons dashicons-games"></span><h3>' . esc_html__( 'No games yet', 'gdp-lite' ) . '</h3><p>' . esc_html__( 'Add your first game or import JSON data to get started.', 'gdp-lite' ) . '</p></div>';
			return;
		}
		echo '<div class="gdp-table-wrap"><table class="gdp-recent-table"><thead><tr><th>' . esc_html__( 'Game', 'gdp-lite' ) . '</th><th>' . esc_html__( 'Provider', 'gdp-lite' ) . '</th><th>' . esc_html__( 'Status', 'gdp-lite' ) . '</th><th>' . esc_html__( 'Updated', 'gdp-lite' ) . '</th></tr></thead><tbody>';
		foreach ( $recent as $game ) {
			$providers = wp_get_post_terms( $game->ID, 'gdp_provider', array( 'fields' => 'names' ) );
			echo '<tr><td><a class="gdp-game-cell" href="' . esc_url( get_edit_post_link( $game->ID ) ) . '"><span class="gdp-recent-thumb">' . get_the_post_thumbnail( $game->ID, array( 48, 48 ) ) . '</span><strong>' . esc_html( get_the_title( $game ) ) . '</strong></a></td><td>' . esc_html( is_wp_error( $providers ) || empty( $providers ) ? '—' : implode( ', ', $providers ) ) . '</td><td><span class="gdp-status gdp-status-' . esc_attr( $game->post_status ) . '">' . esc_html( 'publish' === $game->post_status ? __( 'Published', 'gdp-lite' ) : __( 'Draft', 'gdp-lite' ) ) . '</span></td><td>' . esc_html( get_the_modified_date( '', $game ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public function appearance() {
		$saved = false;
		if ( isset( $_POST['gdp_appearance_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_appearance_nonce'] ) ), 'gdp_save_appearance' ) && current_user_can( 'manage_options' ) ) {
			$appearance = array(
				'radius'      => max( 0, min( 30, absint( $_POST['radius'] ?? 10 ) ) ),
				'shadow'      => in_array( sanitize_key( $_POST['shadow'] ?? 'light' ), array( 'none', 'light', 'medium', 'strong' ), true ) ? sanitize_key( $_POST['shadow'] ) : 'light',
				'container'   => in_array( sanitize_key( $_POST['container'] ?? '1200' ), array( '1200', '1320', '1440', 'full' ), true ) ? sanitize_key( $_POST['container'] ) : '1200',
				'image_ratio' => in_array( sanitize_key( $_POST['image_ratio'] ?? '4-3' ), array( '16-9', '4-3', '1-1', '3-4' ), true ) ? sanitize_key( $_POST['image_ratio'] ) : '4-3',
				'button'      => in_array( sanitize_key( $_POST['button'] ?? 'filled' ), array( 'filled', 'outline', 'soft' ), true ) ? sanitize_key( $_POST['button'] ) : 'filled',
				'hover'       => in_array( sanitize_key( $_POST['hover'] ?? 'scale' ), array( 'lift', 'scale', 'none' ), true ) ? sanitize_key( $_POST['hover'] ) : 'scale',
				'gap'         => max( 0, min( 40, absint( $_POST['gap'] ?? 20 ) ) ),
			);
			update_option( 'gdp_appearance', $appearance );
			$saved = true;
		}
		$a = wp_parse_args( get_option( 'gdp_appearance', array() ), array( 'radius' => 10, 'shadow' => 'light', 'container' => '1200', 'image_ratio' => '4-3', 'button' => 'filled', 'hover' => 'scale', 'gap' => 20 ) );
		?>
		<div class="wrap gdp-admin-wrap"><div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'DESIGN SYSTEM', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Appearance', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Control the front-end game grid without editing CSS.', 'gdp-lite' ); ?></p></div></div><?php if ( $saved ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Appearance saved.', 'gdp-lite' ); ?></div><?php endif; ?><form method="post" class="gdp-card gdp-settings-card gdp-settings-wide"><?php wp_nonce_field( 'gdp_save_appearance', 'gdp_appearance_nonce' ); ?><div class="gdp-field-grid">
		<label class="gdp-field"><span><?php esc_html_e( 'Card radius', 'gdp-lite' ); ?></span><input type="number" min="0" max="30" name="radius" value="<?php echo esc_attr( $a['radius'] ); ?>"><small>0–30px</small></label>
		<label class="gdp-field"><span><?php esc_html_e( 'Shadow', 'gdp-lite' ); ?></span><select name="shadow"><?php foreach ( array( 'none' => 'None', 'light' => 'Light', 'medium' => 'Medium', 'strong' => 'Strong' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $a['shadow'], $value ); ?>><?php echo esc_html( __( $label, 'gdp-lite' ) ); ?></option><?php endforeach; ?></select></label>
		<label class="gdp-field"><span><?php esc_html_e( 'Container width', 'gdp-lite' ); ?></span><select name="container"><?php foreach ( array( '1200' => '1200px', '1320' => '1320px', '1440' => '1440px', 'full' => 'Full Width' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $a['container'], $value ); ?>><?php echo esc_html( __( $label, 'gdp-lite' ) ); ?></option><?php endforeach; ?></select></label>
		<label class="gdp-field"><span><?php esc_html_e( 'Image ratio', 'gdp-lite' ); ?></span><select name="image_ratio"><?php foreach ( array( '16-9' => '16:9', '4-3' => '4:3', '1-1' => '1:1', '3-4' => '3:4' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $a['image_ratio'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
		<label class="gdp-field"><span><?php esc_html_e( 'Button style', 'gdp-lite' ); ?></span><select name="button"><?php foreach ( array( 'filled' => 'Filled', 'outline' => 'Outline', 'soft' => 'Soft' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $a['button'], $value ); ?>><?php echo esc_html( __( $label, 'gdp-lite' ) ); ?></option><?php endforeach; ?></select></label>
		<label class="gdp-field"><span><?php esc_html_e( 'Hover effect', 'gdp-lite' ); ?></span><select name="hover"><?php foreach ( array( 'lift' => 'Lift', 'scale' => 'Scale', 'none' => 'None' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $a['hover'], $value ); ?>><?php echo esc_html( __( $label, 'gdp-lite' ) ); ?></option><?php endforeach; ?></select></label>
		<label class="gdp-field"><span><?php esc_html_e( 'Grid gap', 'gdp-lite' ); ?></span><input type="number" min="0" max="40" name="gap" value="<?php echo esc_attr( $a['gap'] ); ?>"><small>0–40px</small></label>
		</div><p><button class="button button-primary"><?php esc_html_e( 'Save Appearance', 'gdp-lite' ); ?></button></p></form></div>
		<?php
	}

	public function permalinks() {
		$saved = false;
		$errors = array();
		if ( isset( $_POST['gdp_permalink_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_permalink_nonce'] ) ), 'gdp_save_permalinks' ) && current_user_can( 'manage_options' ) ) {
			$bases = array(
				'game'       => sanitize_title( wp_unslash( $_POST['game'] ?? '' ) ),
				'type'       => sanitize_title( wp_unslash( $_POST['type'] ?? '' ) ),
				'provider'   => sanitize_title( wp_unslash( $_POST['provider'] ?? '' ) ),
				'collection' => sanitize_title( wp_unslash( $_POST['collection'] ?? '' ) ),
			);
			$errors = $this->validate_permalink_bases( $bases );
			if ( empty( $errors ) ) {
				update_option( 'gdp_permalink_bases', $bases );
				flush_rewrite_rules();
				$saved = true;
			}
		}
		$bases = GDP_Lite_Post_Types::permalink_bases();
		?>
		<div class="wrap gdp-admin-wrap"><div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'URL STRUCTURE', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Permalinks', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Use readable, conflict-resistant URL bases for GDP content.', 'gdp-lite' ); ?></p></div></div>
		<?php if ( $saved ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Permalinks saved and rewrite rules refreshed.', 'gdp-lite' ); ?></div><?php endif; ?>
		<?php foreach ( $errors as $error ) : ?><div class="gdp-inline-notice is-error"><span class="dashicons dashicons-warning"></span><?php echo esc_html( $error ); ?></div><?php endforeach; ?>
		<form method="post" class="gdp-card gdp-settings-card gdp-settings-wide"><?php wp_nonce_field( 'gdp_save_permalinks', 'gdp_permalink_nonce' ); ?><div class="gdp-field-grid">
		<?php foreach ( array( 'game' => 'Game Base', 'type' => 'Game Type Base', 'provider' => 'Provider Base', 'collection' => 'Collection Base' ) as $key => $label ) : ?><label class="gdp-field"><span><?php echo esc_html( __( $label, 'gdp-lite' ) ); ?></span><div class="gdp-slug-field"><code>/</code><input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $bases[ $key ] ); ?>"><code>/</code></div><small><?php echo esc_html( home_url( '/' . $bases[ $key ] . '/example/' ) ); ?></small></label><?php endforeach; ?>
		</div><p><button class="button button-primary"><?php esc_html_e( 'Save Permalinks', 'gdp-lite' ); ?></button></p></form></div>
		<?php
	}

	private function validate_permalink_bases( $bases ) {
		$errors = array();
		$labels = array( 'game' => 'Game Base', 'type' => 'Game Type Base', 'provider' => 'Provider Base', 'collection' => 'Collection Base' );
		$reserved = array( 'wp-admin', 'wp-content', 'wp-includes', 'feed', 'author', 'search', 'tag', 'category', 'page', 'attachment' );
		foreach ( $bases as $key => $slug ) {
			if ( '' === $slug ) { $errors[] = sprintf( __( '%s cannot be empty.', 'gdp-lite' ), __( $labels[ $key ], 'gdp-lite' ) ); continue; }
			if ( in_array( $slug, $reserved, true ) ) { $errors[] = sprintf( __( 'The permalink base "%s" is reserved by WordPress.', 'gdp-lite' ), $slug ); }
			$page = get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) );
			if ( $page ) { $errors[] = sprintf( __( 'The permalink base "%s" is already used by existing content.', 'gdp-lite' ), $slug ); }
		}
		if ( count( array_unique( $bases ) ) !== count( $bases ) ) { $errors[] = __( 'Each permalink base must be unique.', 'gdp-lite' ); }
		foreach ( get_post_types( array(), 'objects' ) as $name => $object ) {
			if ( 'gdp_game' === $name || empty( $object->rewrite['slug'] ) ) { continue; }
			if ( in_array( trim( $object->rewrite['slug'], '/' ), $bases, true ) ) { $errors[] = sprintf( __( 'The permalink base "%s" conflicts with another post type.', 'gdp-lite' ), $object->rewrite['slug'] ); }
		}
		foreach ( get_taxonomies( array(), 'objects' ) as $name => $object ) {
			if ( in_array( $name, array( 'gdp_game_type', 'gdp_provider', 'gdp_collection', 'gdp_game_category' ), true ) || empty( $object->rewrite['slug'] ) ) { continue; }
			if ( in_array( trim( $object->rewrite['slug'], '/' ), $bases, true ) ) { $errors[] = sprintf( __( 'The permalink base "%s" conflicts with another taxonomy.', 'gdp-lite' ), $object->rewrite['slug'] ); }
		}
		return array_values( array_unique( $errors ) );
	}

	public function settings() {
		$saved = false;
		if ( isset( $_POST['gdp_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_settings_nonce'] ) ), 'gdp_save_settings' ) && current_user_can( 'manage_options' ) ) {
			$columns = isset( $_POST['gdp_default_columns'] ) ? absint( $_POST['gdp_default_columns'] ) : 4;
			update_option( 'gdp_default_columns', max( 1, min( 6, $columns ) ) );
			$language = isset( $_POST['gdp_lite_language'] ) ? sanitize_text_field( wp_unslash( $_POST['gdp_lite_language'] ) ) : 'auto';
			if ( ! in_array( $language, array( 'auto', 'en_US', 'zh_CN' ), true ) ) { $language = 'auto'; }
			update_option( 'gdp_lite_language', $language );
			$saved = true;
		}
		?>
		<div class="wrap gdp-admin-wrap"><div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'CONFIGURATION', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'GDP Lite Settings', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Set general plugin behaviour and language.', 'gdp-lite' ); ?></p></div></div><?php if ( $saved ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Settings saved.', 'gdp-lite' ); ?></div><?php endif; ?><form method="post" class="gdp-card gdp-settings-card"><?php wp_nonce_field( 'gdp_save_settings', 'gdp_settings_nonce' ); ?><div class="gdp-field-grid"><label class="gdp-field"><span><?php esc_html_e( 'Default columns', 'gdp-lite' ); ?></span><input type="number" min="1" max="6" name="gdp_default_columns" value="<?php echo esc_attr( get_option( 'gdp_default_columns', 4 ) ); ?>"><small><?php esc_html_e( 'Used when the shortcode does not include a columns value.', 'gdp-lite' ); ?></small></label><label class="gdp-field"><span><?php esc_html_e( 'Admin Language', 'gdp-lite' ); ?></span><select name="gdp_lite_language"><option value="auto" <?php selected( get_option( 'gdp_lite_language', 'auto' ), 'auto' ); ?>><?php esc_html_e( 'Auto (WordPress)', 'gdp-lite' ); ?></option><option value="en_US" <?php selected( get_option( 'gdp_lite_language', 'auto' ), 'en_US' ); ?>><?php esc_html_e( 'English', 'gdp-lite' ); ?></option><option value="zh_CN" <?php selected( get_option( 'gdp_lite_language', 'auto' ), 'zh_CN' ); ?>><?php esc_html_e( 'Simplified Chinese', 'gdp-lite' ); ?></option></select><small><?php esc_html_e( 'Choose the language used inside GDP Lite admin pages.', 'gdp-lite' ); ?></small></label></div><p><button class="button button-primary"><?php esc_html_e( 'Save Settings', 'gdp-lite' ); ?></button></p></form></div>
		<?php
	}

	public function performance() {
		$saved   = false;
		$cleared = false;
		if ( isset( $_POST['gdp_performance_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_performance_nonce'] ) ), 'gdp_save_performance' ) && current_user_can( 'manage_options' ) ) {
			if ( isset( $_POST['gdp_clear_cache'] ) ) {
				gdp_cache()->flush();
				$cleared = true;
			} else {
				$ttl_values = array( 300, 900, 1800, 3600, 21600, 43200, 86400 );
				$ttl = absint( $_POST['ttl'] ?? 3600 );
				if ( ! in_array( $ttl, $ttl_values, true ) ) { $ttl = 3600; }
				update_option(
					'gdp_lite_performance',
					array(
						'cache_enabled' => isset( $_POST['cache_enabled'] ) ? 1 : 0,
						'ttl'           => $ttl,
					),
					false
				);
				gdp_cache()->flush();
				$saved = true;
			}
		}

		$settings = wp_parse_args( get_option( 'gdp_lite_performance', array() ), array( 'cache_enabled' => 1, 'ttl' => 3600 ) );
		$status   = gdp_cache()->status();
		?>
		<div class="wrap gdp-admin-wrap">
			<div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'PERFORMANCE FRAMEWORK', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Performance', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Control query, REST and taxonomy caching from one place.', 'gdp-lite' ); ?></p></div></div>
			<?php if ( $saved ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Performance settings saved and cache refreshed.', 'gdp-lite' ); ?></div><?php endif; ?>
			<?php if ( $cleared ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-update"></span><?php esc_html_e( 'GDP cache cleared.', 'gdp-lite' ); ?></div><?php endif; ?>
			<section class="gdp-stat-grid">
				<?php $this->stat_card( $status['enabled'] ? __( 'Enabled', 'gdp-lite' ) : __( 'Disabled', 'gdp-lite' ), __( 'GDP Cache', 'gdp-lite' ), 'dashicons-performance', '#' ); ?>
				<?php $this->stat_card( $status['persistent_object_cache'] ? __( 'Active', 'gdp-lite' ) : __( 'Not detected', 'gdp-lite' ), __( 'Persistent Object Cache', 'gdp-lite' ), 'dashicons-database', '#' ); ?>
				<?php $this->stat_card( (int) $status['version'], __( 'Cache Version', 'gdp-lite' ), 'dashicons-update', '#' ); ?>
				<?php $this->stat_card( human_time_diff( time() - (int) $status['ttl'], time() ), __( 'Default TTL', 'gdp-lite' ), 'dashicons-clock', '#' ); ?>
			</section>
			<form method="post" class="gdp-card gdp-settings-card gdp-settings-wide">
				<?php wp_nonce_field( 'gdp_save_performance', 'gdp_performance_nonce' ); ?>
				<div class="gdp-field-grid">
					<label class="gdp-field"><span><?php esc_html_e( 'Cache Engine', 'gdp-lite' ); ?></span><label><input type="checkbox" name="cache_enabled" value="1" <?php checked( ! empty( $settings['cache_enabled'] ) ); ?>> <?php esc_html_e( 'Enable GDP query and REST cache', 'gdp-lite' ); ?></label><small><?php esc_html_e( 'Uses Redis or another persistent object cache when available, with transient fallback.', 'gdp-lite' ); ?></small></label>
					<label class="gdp-field"><span><?php esc_html_e( 'Cache Lifetime', 'gdp-lite' ); ?></span><select name="ttl">
					<?php foreach ( array( 300 => '5 minutes', 900 => '15 minutes', 1800 => '30 minutes', 3600 => '1 hour', 21600 => '6 hours', 43200 => '12 hours', 86400 => '24 hours' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( (int) $settings['ttl'], $value ); ?>><?php echo esc_html( __( $label, 'gdp-lite' ) ); ?></option><?php endforeach; ?>
					</select><small><?php esc_html_e( 'Game and taxonomy changes invalidate cached data automatically.', 'gdp-lite' ); ?></small></label>
				</div>
				<p><button class="button button-primary"><?php esc_html_e( 'Save Performance', 'gdp-lite' ); ?></button> <button class="button" name="gdp_clear_cache" value="1"><?php esc_html_e( 'Clear GDP Cache', 'gdp-lite' ); ?></button></p>
			</form>
			<section class="gdp-card gdp-settings-card gdp-settings-wide"><h2><?php esc_html_e( 'Cache Status', 'gdp-lite' ); ?></h2><dl class="gdp-system-list"><div><dt><?php esc_html_e( 'Backend', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( $status['persistent_object_cache'] ? $status['object_cache_class'] : __( 'WordPress transients', 'gdp-lite' ) ); ?></dd></div><div><dt><?php esc_html_e( 'Last clear', 'gdp-lite' ); ?></dt><dd><?php echo esc_html( $status['last_flush'] ? wp_date( 'Y-m-d H:i:s', $status['last_flush'] ) : __( 'Never', 'gdp-lite' ) ); ?></dd></div></dl></section>
		</div>
		<?php
	}


	public function developer() {
		$saved = false;
		$cleared = false;
		if ( isset( $_POST['gdp_developer_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_developer_nonce'] ) ), 'gdp_save_developer' ) && current_user_can( 'manage_options' ) ) {
			if ( isset( $_POST['gdp_clear_logs'] ) ) {
				gdp_logger()->clear();
				$cleared = true;
			} else {
				$level = isset( $_POST['log_level'] ) ? sanitize_key( wp_unslash( $_POST['log_level'] ) ) : 'info';
				if ( ! in_array( $level, array( 'debug', 'info', 'warning', 'error' ), true ) ) { $level = 'info'; }
				update_option( 'gdp_lite_developer', array(
					'enabled' => isset( $_POST['enabled'] ) ? 1 : 0,
					'query' => isset( $_POST['query'] ) ? 1 : 0,
					'rest' => isset( $_POST['rest'] ) ? 1 : 0,
					'template' => isset( $_POST['template'] ) ? 1 : 0,
					'cache' => isset( $_POST['cache'] ) ? 1 : 0,
					'log_level' => $level,
				), false );
				$saved = true;
			}
		}
		$settings = gdp_logger()->settings();
		$logs = gdp_logger()->get_logs();
		$cache = gdp_cache()->status();
		$theme_dir = trailingslashit( get_stylesheet_directory() ) . 'gdp-lite/';
		$overrides = array();
		foreach ( array( 'archive-game.php', 'single-game.php', 'taxonomy-game-type.php', 'taxonomy-game-provider.php', 'taxonomy-game-collection.php', 'parts/game-card.php' ) as $template ) {
			$overrides[ $template ] = file_exists( $theme_dir . $template );
		}
		?>
		<div class="wrap gdp-admin-wrap">
			<div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'DEVELOPER FRAMEWORK', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Developer Tools', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Inspect queries, REST requests, templates, cache and runtime diagnostics.', 'gdp-lite' ); ?></p></div></div>
			<?php if ( $saved ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e( 'Developer settings saved.', 'gdp-lite' ); ?></div><?php endif; ?>
			<?php if ( $cleared ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-trash"></span><?php esc_html_e( 'Developer logs cleared.', 'gdp-lite' ); ?></div><?php endif; ?>
			<section class="gdp-stat-grid">
				<?php $this->stat_card( ! empty( $settings['enabled'] ) ? __( 'Enabled', 'gdp-lite' ) : __( 'Disabled', 'gdp-lite' ), __( 'Developer Mode', 'gdp-lite' ), 'dashicons-editor-code', '#' ); ?>
				<?php $this->stat_card( count( $logs ), __( 'Stored Logs', 'gdp-lite' ), 'dashicons-list-view', '#' ); ?>
				<?php $this->stat_card( $cache['runtime']['hits'] . ' / ' . $cache['runtime']['misses'], __( 'Cache Hits / Misses', 'gdp-lite' ), 'dashicons-performance', '#' ); ?>
				<?php $this->stat_card( count( array_filter( $overrides ) ), __( 'Theme Overrides', 'gdp-lite' ), 'dashicons-admin-appearance', '#' ); ?>
			</section>
			<form method="post" class="gdp-card gdp-settings-card gdp-settings-wide">
				<?php wp_nonce_field( 'gdp_save_developer', 'gdp_developer_nonce' ); ?>
				<h2><?php esc_html_e( 'Diagnostic Logging', 'gdp-lite' ); ?></h2>
				<div class="gdp-field-grid">
					<label class="gdp-field"><span><?php esc_html_e( 'Developer Mode', 'gdp-lite' ); ?></span><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php esc_html_e( 'Enable diagnostic logging', 'gdp-lite' ); ?></label><small><?php esc_html_e( 'Keep disabled on production unless troubleshooting. Logs are capped at 200 entries.', 'gdp-lite' ); ?></small></label>
					<label class="gdp-field"><span><?php esc_html_e( 'Minimum Log Level', 'gdp-lite' ); ?></span><select name="log_level"><?php foreach ( array( 'debug', 'info', 'warning', 'error' ) as $level ) : ?><option value="<?php echo esc_attr( $level ); ?>" <?php selected( $settings['log_level'], $level ); ?>><?php echo esc_html( ucfirst( $level ) ); ?></option><?php endforeach; ?></select></label>
				</div>
				<div class="gdp-field-grid">
					<?php foreach ( array( 'query' => 'Query', 'rest' => 'REST', 'template' => 'Template', 'cache' => 'Cache' ) as $key => $label ) : ?><label class="gdp-field"><span><?php echo esc_html( $label ); ?></span><label><input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>> <?php echo esc_html( sprintf( __( 'Record %s diagnostics', 'gdp-lite' ), $label ) ); ?></label></label><?php endforeach; ?>
				</div>
				<p><button class="button button-primary"><?php esc_html_e( 'Save Developer Settings', 'gdp-lite' ); ?></button> <button class="button" name="gdp_clear_logs" value="1"><?php esc_html_e( 'Clear Logs', 'gdp-lite' ); ?></button></p>
			</form>
			<section class="gdp-card gdp-settings-card gdp-settings-wide"><h2><?php esc_html_e( 'System Diagnostics', 'gdp-lite' ); ?></h2><dl class="gdp-system-list">
				<div><dt>WordPress</dt><dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd></div><div><dt>PHP</dt><dd><?php echo esc_html( PHP_VERSION ); ?></dd></div><div><dt>GDP Lite</dt><dd><?php echo esc_html( GDP_LITE_VERSION ); ?></dd></div><div><dt>Database</dt><dd><?php echo esc_html( get_option( 'gdp_lite_db_version', '—' ) ); ?></dd></div><div><dt>REST API</dt><dd><code><?php echo esc_html( rest_url( 'gdp/v1/games' ) ); ?></code></dd></div><div><dt>Object Cache</dt><dd><?php echo esc_html( $cache['persistent_object_cache'] ? __( 'Persistent cache active', 'gdp-lite' ) : __( 'Transient fallback', 'gdp-lite' ) ); ?></dd></div>
			</dl></section>
			<section class="gdp-card gdp-settings-card gdp-settings-wide"><h2><?php esc_html_e( 'Template Overrides', 'gdp-lite' ); ?></h2><dl class="gdp-system-list"><?php foreach ( $overrides as $template => $active ) : ?><div><dt><code><?php echo esc_html( $template ); ?></code></dt><dd><?php echo esc_html( $active ? __( 'Theme override active', 'gdp-lite' ) : __( 'Plugin default', 'gdp-lite' ) ); ?></dd></div><?php endforeach; ?></dl></section>
			<section class="gdp-card gdp-settings-card gdp-settings-wide"><h2><?php esc_html_e( 'Recent Logs', 'gdp-lite' ); ?></h2><?php if ( empty( $logs ) ) : ?><p><?php esc_html_e( 'No diagnostic entries recorded.', 'gdp-lite' ); ?></p><?php else : ?><div class="gdp-log-table"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'gdp-lite' ); ?></th><th><?php esc_html_e( 'Level', 'gdp-lite' ); ?></th><th><?php esc_html_e( 'Channel', 'gdp-lite' ); ?></th><th><?php esc_html_e( 'Message', 'gdp-lite' ); ?></th><th><?php esc_html_e( 'Context', 'gdp-lite' ); ?></th></tr></thead><tbody><?php foreach ( array_slice( $logs, 0, 50 ) as $entry ) : ?><tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i:s', (int) $entry['time'] ) ); ?></td><td><code><?php echo esc_html( strtoupper( $entry['level'] ) ); ?></code></td><td><?php echo esc_html( $entry['channel'] ); ?></td><td><?php echo esc_html( $entry['message'] ); ?></td><td><code><?php echo esc_html( wp_json_encode( $entry['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></code></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
		</div>
		<?php
	}


	public function health() {
		$checks = GDP()->health()->checks();
		$passed = count( array_filter( $checks, static function( $check ) { return ! empty( $check['ok'] ); } ) );
		?>
		<div class="wrap gdp-admin-wrap">
			<div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'LTS DIAGNOSTICS', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Health Check', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Verify the production environment before enabling extensions.', 'gdp-lite' ); ?></p></div></div>
			<section class="gdp-stat-grid"><?php $this->stat_card( $passed . ' / ' . count( $checks ), __( 'Checks Passed', 'gdp-lite' ), 'dashicons-shield-alt', '#' ); ?><?php $this->stat_card( GDP_LITE_VERSION, __( 'Core Version', 'gdp-lite' ), 'dashicons-info-outline', '#' ); ?><?php $this->stat_card( GDP_LITE_DB_VERSION, __( 'Database Version', 'gdp-lite' ), 'dashicons-database', '#' ); ?></section>
			<section class="gdp-card gdp-settings-card gdp-settings-wide"><h2><?php esc_html_e( 'Environment Status', 'gdp-lite' ); ?></h2><dl class="gdp-system-list"><?php foreach ( $checks as $check ) : ?><div><dt><?php echo esc_html( $check['label'] ); ?></dt><dd><span class="dashicons <?php echo esc_attr( $check['ok'] ? 'dashicons-yes-alt' : 'dashicons-warning' ); ?>"></span> <?php echo esc_html( $check['value'] ); ?></dd></div><?php endforeach; ?></dl></section>
		</div>
		<?php
	}

	public function columns( $columns ) {
		return array(
			'cb'             => $columns['cb'],
			'gdp_thumb'      => __( 'Image', 'gdp-lite' ),
			'title'          => __( 'Game', 'gdp-lite' ),
			'gdp_provider'   => __( 'Provider', 'gdp-lite' ),
			'gdp_type'       => __( 'Type', 'gdp-lite' ),
			'gdp_rtp'        => __( 'RTP', 'gdp-lite' ),
			'gdp_volatility' => __( 'Volatility', 'gdp-lite' ),
			'modified'       => __( 'Updated', 'gdp-lite' ),
		);
	}

	public function column_content( $column, $post_id ) {
		if ( 'gdp_thumb' === $column ) {
			$thumb = get_the_post_thumbnail( $post_id, array( 56, 56 ), array( 'class' => 'gdp-list-thumb' ) );
			echo $thumb ? $thumb : '<span class="gdp-list-placeholder dashicons dashicons-format-image"></span>';
		} elseif ( 'gdp_provider' === $column || 'gdp_type' === $column ) {
			$taxonomy = 'gdp_provider' === $column ? 'gdp_provider' : 'gdp_game_type';
			$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
			echo esc_html( is_wp_error( $terms ) || empty( $terms ) ? '—' : implode( ', ', $terms ) );
		} elseif ( 'gdp_rtp' === $column ) {
			$value = get_post_meta( $post_id, '_gdp_rtp', true );
			echo $value ? '<span class="gdp-data-pill">' . esc_html( $value ) . '%</span>' : '—';
		} elseif ( 'gdp_volatility' === $column ) {
			$value = get_post_meta( $post_id, '_gdp_volatility', true );
			echo $value ? '<span class="gdp-pill gdp-pill-' . esc_attr( $value ) . '">' . esc_html( __( ucfirst( $value ), 'gdp-lite' ) ) . '</span>' : '—';
		} elseif ( 'modified' === $column ) {
			echo esc_html( get_the_modified_date( '', $post_id ) );
		}
	}

	public function filters() {
		global $typenow;
		if ( 'gdp_game' !== $typenow ) {
			return;
		}
		foreach ( array( 'gdp_provider', 'gdp_game_type', 'gdp_collection' ) as $taxonomy ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			if ( ! $taxonomy_object ) {
				continue;
			}
			$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';
			wp_dropdown_categories( array( 'show_option_all' => $taxonomy_object->labels->all_items, 'taxonomy' => $taxonomy, 'name' => $taxonomy, 'orderby' => 'name', 'selected' => $selected, 'hierarchical' => true, 'hide_empty' => false, 'value_field' => 'slug' ) );
		}
	}
}
