<?php
/**
 * Import / Export engine.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Importer {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	public function register_hooks() {
		add_action( 'admin_post_gdp_import_data', array( $this, 'import' ) );
		add_action( 'admin_post_gdp_export_data', array( $this, 'export' ) );
	}

	public static function render_page() {
		$imported = isset( $_GET['imported'] ) ? absint( $_GET['imported'] ) : null;
		$skipped  = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : null;
		$error    = isset( $_GET['gdp_error'] ) ? sanitize_text_field( wp_unslash( $_GET['gdp_error'] ) ) : '';
		?>
		<div class="wrap gdp-admin-wrap">
			<div class="gdp-page-title"><div><span class="gdp-eyebrow"><?php esc_html_e( 'DATA PORTABILITY', 'gdp-lite' ); ?></span><h1><?php esc_html_e( 'Import / Export', 'gdp-lite' ); ?></h1><p><?php esc_html_e( 'Move your complete game database between WordPress sites using JSON or CSV.', 'gdp-lite' ); ?></p></div></div>
			<?php if ( null !== $imported ) : ?><div class="gdp-inline-notice is-success"><span class="dashicons dashicons-yes-alt"></span><?php echo esc_html( sprintf( __( '%1$d games imported or updated. %2$d rows skipped.', 'gdp-lite' ), $imported, (int) $skipped ) ); ?></div><?php endif; ?>
			<?php if ( $error ) : ?><div class="gdp-inline-notice is-error"><span class="dashicons dashicons-warning"></span><?php echo esc_html( $error ); ?></div><?php endif; ?>

			<div class="gdp-dashboard-grid">
				<section class="gdp-card gdp-card-wide">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'IMPORT', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Import Games', 'gdp-lite' ); ?></h2><p><?php esc_html_e( 'Upload a .json or .csv file, or paste a JSON array. Existing games are matched by slug.', 'gdp-lite' ); ?></p></div></div>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="action" value="gdp_import_data">
						<?php wp_nonce_field( 'gdp_import_data', 'gdp_import_nonce' ); ?>
						<div class="gdp-field-grid">
							<label class="gdp-field"><span><?php esc_html_e( 'Import file', 'gdp-lite' ); ?></span><input type="file" name="gdp_import_file" accept=".json,.csv,application/json,text/csv"><small><?php esc_html_e( 'Maximum size follows your WordPress upload limit.', 'gdp-lite' ); ?></small></label>
							<label class="gdp-field"><span><?php esc_html_e( 'Existing game behaviour', 'gdp-lite' ); ?></span><select name="existing"><option value="update"><?php esc_html_e( 'Update matching slugs', 'gdp-lite' ); ?></option><option value="skip"><?php esc_html_e( 'Skip matching slugs', 'gdp-lite' ); ?></option></select></label>
							<label class="gdp-field"><span><?php esc_html_e( 'Post status', 'gdp-lite' ); ?></span><select name="post_status"><option value="preserve"><?php esc_html_e( 'Preserve file value', 'gdp-lite' ); ?></option><option value="publish"><?php esc_html_e( 'Publish all', 'gdp-lite' ); ?></option><option value="draft"><?php esc_html_e( 'Save all as draft', 'gdp-lite' ); ?></option></select></label>
						</div>
						<label class="gdp-field"><span><?php esc_html_e( 'Or paste JSON', 'gdp-lite' ); ?></span><textarea name="gdp_json" rows="10" class="large-text code" placeholder='[{"name":"Super Ace","slug":"super-ace","provider":["jili"],"type":["slots"],"rtp":"96.5"}]'></textarea></label>
						<p><button class="button button-primary"><?php esc_html_e( 'Start Import', 'gdp-lite' ); ?></button></p>
					</form>
				</section>

				<section class="gdp-card">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'EXPORT', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Export Games', 'gdp-lite' ); ?></h2><p><?php esc_html_e( 'Download games with content, taxonomies, metadata and featured image URLs.', 'gdp-lite' ); ?></p></div></div>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="gdp_export_data">
						<?php wp_nonce_field( 'gdp_export_data', 'gdp_export_nonce' ); ?>
						<label class="gdp-field"><span><?php esc_html_e( 'Format', 'gdp-lite' ); ?></span><select name="format"><option value="json">JSON</option><option value="csv">CSV</option></select></label>
						<label class="gdp-field"><span><?php esc_html_e( 'Status', 'gdp-lite' ); ?></span><select name="status"><option value="any"><?php esc_html_e( 'Published and drafts', 'gdp-lite' ); ?></option><option value="publish"><?php esc_html_e( 'Published only', 'gdp-lite' ); ?></option><option value="draft"><?php esc_html_e( 'Drafts only', 'gdp-lite' ); ?></option></select></label>
						<p><button class="button button-primary"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Export', 'gdp-lite' ); ?></button></p>
					</form>
				</section>

				<section class="gdp-card">
					<div class="gdp-card-head"><div><span class="gdp-card-kicker"><?php esc_html_e( 'COMPATIBILITY', 'gdp-lite' ); ?></span><h2><?php esc_html_e( 'Portable fields', 'gdp-lite' ); ?></h2></div></div>
					<p><?php esc_html_e( 'Exports include title, slug, content, excerpt, status, dates, provider, type, collection, category, RTP, volatility, max win, featured state, features, custom game fields and image URL.', 'gdp-lite' ); ?></p>
				</section>
			</div>
		</div>
		<?php
	}

	public function import() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['gdp_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_import_nonce'] ) ), 'gdp_import_data' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'gdp-lite' ) );
		}

		$raw = '';
		$ext = 'json';
		if ( ! empty( $_FILES['gdp_import_file']['tmp_name'] ) ) {
			$file = $_FILES['gdp_import_file'];
			$ext  = strtolower( pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, array( 'json', 'csv' ), true ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
				$this->redirect_error( __( 'Please upload a valid JSON or CSV file.', 'gdp-lite' ) );
			}
			$raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} elseif ( ! empty( $_POST['gdp_json'] ) ) {
			$raw = wp_unslash( $_POST['gdp_json'] );
		} else {
			$this->redirect_error( __( 'No import data was provided.', 'gdp-lite' ) );
		}

		$items = 'csv' === $ext ? $this->parse_csv( $raw ) : json_decode( $raw, true );
		if ( ! is_array( $items ) || json_last_error() !== JSON_ERROR_NONE && 'csv' !== $ext ) {
			$this->redirect_error( __( 'The import data could not be parsed.', 'gdp-lite' ) );
		}

		$existing_mode = isset( $_POST['existing'] ) && 'skip' === sanitize_key( $_POST['existing'] ) ? 'skip' : 'update';
		$status_mode   = isset( $_POST['post_status'] ) ? sanitize_key( $_POST['post_status'] ) : 'preserve';
		$imported = 0;
		$skipped  = 0;

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['name'] ) && empty( $item['title'] ) ) { ++$skipped; continue; }
			$title    = sanitize_text_field( $item['name'] ?? $item['title'] );
			$slug     = ! empty( $item['slug'] ) ? sanitize_title( $item['slug'] ) : sanitize_title( $title );
			$existing = get_page_by_path( $slug, OBJECT, 'gdp_game' );
			if ( $existing && 'skip' === $existing_mode ) { ++$skipped; continue; }
			$status = in_array( $status_mode, array( 'publish', 'draft' ), true ) ? $status_mode : sanitize_key( $item['status'] ?? 'publish' );
			if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ) { $status = 'publish'; }
			$postarr = array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'gdp_game',
				'post_status'  => $status,
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => isset( $item['description'] ) ? wp_kses_post( $item['description'] ) : wp_kses_post( $item['content'] ?? '' ),
				'post_excerpt' => isset( $item['excerpt'] ) ? sanitize_textarea_field( $item['excerpt'] ) : '',
			);
			if ( ! empty( $item['date'] ) ) { $postarr['post_date'] = sanitize_text_field( $item['date'] ); }
			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) { ++$skipped; continue; }

			$this->import_meta( $post_id, $item );
			$this->import_terms( $post_id, $item );
			if ( ! empty( $item['image_url'] ) ) { $this->maybe_import_image( $post_id, esc_url_raw( $item['image_url'] ) ); }
			++$imported;
		}
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		clean_post_cache( 0 );
		wp_safe_redirect( add_query_arg( array( 'page' => 'gdp-lite-import', 'imported' => $imported, 'skipped' => $skipped ), admin_url( 'admin.php' ) ) ); exit;
	}

	private function import_meta( $post_id, $item ) { return GDP_Lite_Data_Portability::import_fields( $post_id, $item ); }

	private function import_terms( $post_id, $item ) {
		foreach ( array( 'type' => 'gdp_game_type', 'provider' => 'gdp_provider', 'collection' => 'gdp_collection', 'category' => 'gdp_game_category' ) as $key => $taxonomy ) {
			if ( ! isset( $item[ $key ] ) || '' === $item[ $key ] ) { continue; }
			$terms = is_array( $item[ $key ] ) ? $item[ $key ] : preg_split( '/[|,]/', (string) $item[ $key ] );
			$terms = array_values( array_filter( array_map( 'sanitize_text_field', $terms ) ) );
			wp_set_object_terms( $post_id, $terms, $taxonomy, false );
		}
	}

	private function maybe_import_image( $post_id, $url ) {
		if ( has_post_thumbnail( $post_id ) || ! wp_http_validate_url( $url ) ) { return; }
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		media_sideload_image( $url, $post_id, get_the_title( $post_id ), 'id' );
	}

	private function parse_csv( $raw ) {
		$handle = fopen( 'php://temp', 'r+' );
		fwrite( $handle, preg_replace( '/^\xEF\xBB\xBF/', '', (string) $raw ) );
		rewind( $handle );
		$headers = fgetcsv( $handle );
		if ( ! is_array( $headers ) ) { fclose( $handle ); return array(); }
		$headers = array_map( 'sanitize_key', $headers );
		$rows = array();
		while ( false !== ( $values = fgetcsv( $handle ) ) ) {
			$values = array_pad( $values, count( $headers ), '' );
			$rows[] = array_combine( $headers, array_slice( $values, 0, count( $headers ) ) );
		}
		fclose( $handle );
		return $rows;
	}

	public function export() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['gdp_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdp_export_nonce'] ) ), 'gdp_export_data' ) ) { wp_die( esc_html__( 'Permission denied.', 'gdp-lite' ) ); }
		$format = isset( $_POST['format'] ) && 'csv' === sanitize_key( $_POST['format'] ) ? 'csv' : 'json';
		$status = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'any';
		if ( ! in_array( $status, array( 'any', 'publish', 'draft' ), true ) ) { $status = 'any'; }
		$posts = get_posts( array( 'post_type' => 'gdp_game', 'post_status' => 'any' === $status ? array( 'publish', 'draft', 'pending', 'private' ) : $status, 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
		$data = array_map( array( $this, 'prepare_export_item' ), $posts );
		$filename = 'gdp-games-' . gmdate( 'Y-m-d-His' ) . '.' . $format;
		nocache_headers();
		header( 'Content-Disposition: attachment; filename=' . $filename );
		if ( 'json' === $format ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		} else {
			header( 'Content-Type: text/csv; charset=utf-8' );
			$out = fopen( 'php://output', 'w' );
			echo "\xEF\xBB\xBF";
			$headers = array_merge( array( 'name','slug','status','date','description','excerpt','provider','type','collection','category','image_url' ), array_keys( GDP_Lite_Data_Portability::schema()['fields'] ) );
			fputcsv( $out, $headers );
			foreach ( $data as $row ) {
				$flat = array();
				foreach ( $headers as $key ) { $flat[] = is_array( $row[ $key ] ?? '' ) ? implode( '|', $row[ $key ] ) : ( $row[ $key ] ?? '' ); }
				fputcsv( $out, $flat );
			}
			fclose( $out );
		}
		exit;
	}

	private function prepare_export_item( $post ) {
		$item = array(
			'name' => $post->post_title, 'slug' => $post->post_name, 'status' => $post->post_status,
			'date' => $post->post_date, 'description' => $post->post_content, 'excerpt' => $post->post_excerpt,
			'provider' => $this->term_slugs( $post->ID, 'gdp_provider' ), 'type' => $this->term_slugs( $post->ID, 'gdp_game_type' ),
			'collection' => $this->term_slugs( $post->ID, 'gdp_collection' ), 'category' => $this->term_slugs( $post->ID, 'gdp_game_category' ),
			'image_url' => get_the_post_thumbnail_url( $post->ID, 'full' ) ?: '',
		);
		$fields = GDP_Lite_Data_Portability::export_fields( $post->ID );
		$item['fields'] = $fields;
		$item += $fields;
		return apply_filters( 'gdp_lite_export_game', $item, $post );
	}

	private function term_slugs( $post_id, $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
		return is_wp_error( $terms ) ? array() : $terms;
	}

	private function redirect_error( $message ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'gdp-lite-import', 'gdp_error' => rawurlencode( $message ) ), admin_url( 'admin.php' ) ) ); exit;
	}
}
