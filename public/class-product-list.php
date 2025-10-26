<?php
/**
 * Product List Shortcode Handler
 *
 * Renders the [tmw_products] shortcode with search and infinite scroll.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Product_List class
 *
 * Handles the front-end product list shortcode.
 */
class TMW_Product_List {

	/**
	 * Current search query
	 *
	 * @var string
	 */
	private $current_search_query = '';

	/**
	 * Initialize the class and set up hooks
	 */
	public function init() {
		add_shortcode( 'tmw_products', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the [tmw_products] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 50,  // initial chunk size
				'add_url'  => '',  // optional: show Add Product button linking here
			),
			$atts,
			'tmw_products'
		);

		$per_page = min( 500, max( 1, intval( $_GET['per'] ?? $atts['per_page'] ) ) );
		$paged    = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$qtxt     = sanitize_text_field( $_GET['q'] ?? '' );
		$catid    = intval( $_GET['cat'] ?? 0 );

		$cats  = get_terms( array( 'taxonomy' => TMW_Config::CATEGORY_TAX, 'hide_empty' => false ) );
		$nonce = wp_create_nonce( TMW_Config::NONCE_PRODUCTS );
		$add_url = esc_url( $atts['add_url'] );

		// SERVER-SIDE: Query initial products
		$args = array(
			'post_type'      => TMW_Config::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);

		// Add search query (custom implementation to search both title and meta fields)
		if ( $qtxt ) {
			// Store search term for custom filter
			add_filter( 'posts_search', array( $this, 'custom_search_filter' ), 10, 2 );
			add_filter( 'posts_join', array( $this, 'custom_search_join' ), 10, 2 );
			add_filter( 'posts_groupby', array( $this, 'custom_search_groupby' ), 10, 2 );

			// Store the search query as a class property for the filter
			$this->current_search_query = $qtxt;
		}

		// Add category filter
		if ( $catid ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => TMW_Config::CATEGORY_TAX,
					'field'    => 'term_id',
					'terms'    => $catid,
				),
			);
		}

		$query = new WP_Query( $args );

		// Remove search filters after query
		if ( $qtxt ) {
			remove_filter( 'posts_search', array( $this, 'custom_search_filter' ), 10 );
			remove_filter( 'posts_join', array( $this, 'custom_search_join' ), 10 );
			remove_filter( 'posts_groupby', array( $this, 'custom_search_groupby' ), 10 );
			$this->current_search_query = '';
		}

		// Enqueue assets
		$this->enqueue_assets( $per_page, $qtxt, $catid, $nonce, $query->max_num_pages );

		// Render HTML
		ob_start();
		?>
		<style>
		@view-transition {
			navigation: auto;
		}

		html {
			scroll-behavior: smooth;
		}

		.tmw-wrapper {
			width: 100%;
			max-width: 80rem;
			margin-left: auto;
			margin-right: auto;
			padding-top: 2rem;
			padding-bottom: 2rem;
		}

		.tmw-content {
			background: #fff;
			border: 1px solid #e8e8e8;
			border-radius: 12px;
			padding: 20px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
		}

		.tmw-wrap {
			background: #fff;
			border: 1px solid #e6e6e6;
			border-radius: 8px;
			padding: 16px;
			box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
		}

		.tmw-filter {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			margin-bottom: 10px;
		}

		.tmw-filter input {
			padding: 6px;
			min-width: 340px;
		}

		.tmw-filter select {
			padding: 6px;
			min-width: 200px;
		}

		.tmw-table-container {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}

		.tmw-table {
			width: 100%;
			border-collapse: collapse;
		}

		.tmw-table th,
		.tmw-table td {
			padding: 8px 10px;
			border-bottom: 1px solid #f0f0f0;
			text-align: left;
			white-space: nowrap;
		}

		.tmw-table .tmw-col-sku {
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		}

		.tmw-table tbody tr {
			opacity: 0;
			animation: fadeInRow 0.4s ease-out forwards;
		}

		.tmw-table tbody tr:nth-child(1) { animation-delay: 0.02s; }
		.tmw-table tbody tr:nth-child(2) { animation-delay: 0.04s; }
		.tmw-table tbody tr:nth-child(3) { animation-delay: 0.06s; }
		.tmw-table tbody tr:nth-child(4) { animation-delay: 0.08s; }
		.tmw-table tbody tr:nth-child(5) { animation-delay: 0.10s; }
		.tmw-table tbody tr:nth-child(6) { animation-delay: 0.12s; }
		.tmw-table tbody tr:nth-child(7) { animation-delay: 0.14s; }
		.tmw-table tbody tr:nth-child(8) { animation-delay: 0.16s; }
		.tmw-table tbody tr:nth-child(9) { animation-delay: 0.18s; }
		.tmw-table tbody tr:nth-child(10) { animation-delay: 0.20s; }
		.tmw-table tbody tr:nth-child(11) { animation-delay: 0.22s; }
		.tmw-table tbody tr:nth-child(12) { animation-delay: 0.24s; }
		.tmw-table tbody tr:nth-child(13) { animation-delay: 0.26s; }
		.tmw-table tbody tr:nth-child(14) { animation-delay: 0.28s; }
		.tmw-table tbody tr:nth-child(15) { animation-delay: 0.30s; }
		.tmw-table tbody tr:nth-child(16) { animation-delay: 0.32s; }
		.tmw-table tbody tr:nth-child(17) { animation-delay: 0.34s; }
		.tmw-table tbody tr:nth-child(18) { animation-delay: 0.38s; }
		.tmw-table tbody tr:nth-child(19) { animation-delay: 0.38s; }
		.tmw-table tbody tr:nth-child(20) { animation-delay: 0.40s; }

		@keyframes fadeInRow {
			from {
				opacity: 0;
				transform: translateY(10px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.tmw-table tbody tr:nth-child(odd) {
			background: #fafafa;
		}

		.tmw-table tbody tr:hover {
			background: #f5faff;
		}

		.tmw-table thead th {
			position: sticky;
			top: 0;
			background: #f8f8f8;
			z-index: 2;
			border-bottom: 2px solid #e5e5e5;
		}

		.tmw-sentinel {
			height: 1px;
		}

		.tmw-loader {
			text-align: center;
			padding: 20px;
			color: #666;
			font-style: italic;
		}

		.tmw-counter {
			text-align: center;
			padding: 16px 20px;
			color: #666;
			font-size: 14px;
			border-top: 1px solid #f0f0f0;
			margin-top: 12px;
		}

		.tmw-counter #tmw-count {
			font-weight: 600;
			color: #333;
		}

		.tmw-add-btn {
			padding: 8px 12px;
			background: #1e73be;
			color: #fff;
			border-radius: 4px;
			display: inline-block;
			text-decoration: none;
		}

		.tmw-filter-form {
			display: flex;
			gap: 12px;
			margin-bottom: 24px;
			flex-wrap: wrap;
			align-items: center;
		}

		.tmw-search-input,
		.tmw-field-select,
		.tmw-category-select {
			padding: 10px 12px;
			border: 1px solid #ddd;
			border-radius: 6px;
			font-size: 14px;
		}

		.tmw-field-select {
			min-width: 140px;
		}

		.tmw-search-input {
			width: 100%;
			max-width: 375px !important;
		}

		.tmw-category-select {
			min-width: 180px;
		}

		.tmw-btn-reset {
			padding: 10px 16px;
			background: #f5f5f5;
			color: #333;
			border-radius: 6px;
			text-decoration: none;
			border: 1px solid #ddd;
			transition: background 0.2s;
		}

		.tmw-btn-reset:hover {
			background: #e8e8e8;
		}

		.tmw-archive-title {
			margin-top: 0;
			margin-bottom: 20px;
		}
		</style>
		<div class="tmw-wrap" id="tmw-products">
			<?php if ( $add_url && is_user_logged_in() && current_user_can( 'edit_posts' ) ) : ?>
				<div style="margin-bottom:10px;text-align:right">
					<a class="tmw-add-btn" href="<?php echo $add_url; ?>">+ Add Product</a>
				</div>
			<?php endif; ?>

			<form method="get" class="tmw-filter" id="tmw-filter-form">
				<input type="text" name="q" value="<?php echo esc_attr( $qtxt ); ?>" placeholder="Search title, SKU, vendor, type, configuration, keywords">
				<button type="submit">Search</button>
			</form>

			<table class="tmw-table" id="tmw-table">
				<thead>
					<tr>
						<th class="tmw-col-title">Title</th>
						<th>SKU</th>
						<th>Category</th>
						<th>Vendor</th>
						<th>Type</th>
						<th>Configuration</th>
					</tr>
				</thead>
				<tbody id="tmw-tbody">
					<?php
					// SERVER-SIDE RENDER: Initial products
					if ( $query->have_posts() ) :
						while ( $query->have_posts() ) :
							$query->the_post();
							echo $this->render_product_row( get_the_ID() );
						endwhile;
						wp_reset_postdata();
					else :
						?>
						<tr>
							<td colspan="6" style="text-align:center;padding:20px;color:#999;">
								No products found.
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $query->max_num_pages > 1 ) : ?>
				<div class="tmw-sentinel" id="tmw-sentinel"></div>
			<?php endif; ?>

			<!-- Fallback pagination for no-JS users -->
			<noscript>
				<div class="tmw-pagination" style="margin-top:20px;text-align:center;">
					<?php
					if ( $paged > 1 ) :
						$prev_url = add_query_arg( 'paged', $paged - 1 );
						echo '<a href="' . esc_url( $prev_url ) . '" class="tmw-btn">← Previous</a> ';
					endif;

					if ( $paged < $query->max_num_pages ) :
						$next_url = add_query_arg( 'paged', $paged + 1 );
						echo '<a href="' . esc_url( $next_url ) . '" class="tmw-btn">Next →</a>';
					endif;
					?>
				</div>
			</noscript>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single product table row
	 *
	 * @param int $post_id Product post ID.
	 * @return string HTML table row
	 */
	private function render_product_row( $post_id ) {
		$sku   = get_post_meta( $post_id, TMW_Config::FIELD_SKU, true );
		$vend  = get_post_meta( $post_id, TMW_Config::FIELD_VENDOR, true );
		$type  = get_post_meta( $post_id, TMW_Config::FIELD_TYPE, true );
		$conf  = get_post_meta( $post_id, TMW_Config::FIELD_CONFIG, true );
		$terms = get_the_terms( $post_id, TMW_Config::CATEGORY_TAX );
		$catn  = $terms && ! is_wp_error( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';

		// Create a unique ID from post slug for anchor linking
		$post_slug = get_post_field( 'post_name', $post_id );

		$row = '<tr class="tmw-row" id="product-' . esc_attr( $post_slug ) . '">';
		$row .= '<td class="tmw-col-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></td>';
		$row .= '<td class="tmw-col-sku">' . esc_html( $sku ) . '</td>';
		$row .= '<td>' . esc_html( $catn ) . '</td>';
		$row .= '<td>' . esc_html( $vend ) . '</td>';
		$row .= '<td>' . esc_html( $type ) . '</td>';
		$row .= '<td>' . esc_html( $conf ) . '</td>';
		$row .= '</tr>';

		return $row;
	}

	/**
	 * Custom search filter to search both post title and meta fields
	 *
	 * @param string   $search Search SQL.
	 * @param WP_Query $query Query object.
	 * @return string Modified search SQL
	 */
	public function custom_search_filter( $search, $query ) {
		global $wpdb;

		if ( empty( $this->current_search_query ) ) {
			return $search;
		}

		$search_term = $wpdb->esc_like( $this->current_search_query );
		$search_term = '%' . $search_term . '%';

		// Get searchable meta fields
		$fields = TMW_Config::get_searchable_fields();

		// Build meta field search conditions
		$meta_conditions = array();
		foreach ( $fields as $field ) {
			$meta_conditions[] = $wpdb->prepare( "(pm.meta_key = %s AND pm.meta_value LIKE %s)", $field, $search_term );
		}
		$meta_sql = implode( ' OR ', $meta_conditions );

		// Build search that includes title OR meta fields
		$search = $wpdb->prepare(
			" AND (({$wpdb->posts}.post_title LIKE %s) OR ({$meta_sql}))",
			$search_term
		);

		return $search;
	}

	/**
	 * Join postmeta table for custom search
	 *
	 * @param string   $join Join SQL.
	 * @param WP_Query $query Query object.
	 * @return string Modified join SQL
	 */
	public function custom_search_join( $join, $query ) {
		global $wpdb;

		if ( empty( $this->current_search_query ) ) {
			return $join;
		}

		$join .= " LEFT JOIN {$wpdb->postmeta} AS pm ON ({$wpdb->posts}.ID = pm.post_id)";

		return $join;
	}

	/**
	 * Group by post ID to prevent duplicate results
	 *
	 * @param string   $groupby Group by SQL.
	 * @param WP_Query $query Query object.
	 * @return string Modified group by SQL
	 */
	public function custom_search_groupby( $groupby, $query ) {
		global $wpdb;

		if ( empty( $this->current_search_query ) ) {
			return $groupby;
		}

		if ( ! empty( $groupby ) ) {
			$groupby .= ', ';
		}
		$groupby .= "{$wpdb->posts}.ID";

		return $groupby;
	}

	/**
	 * Enqueue assets for the product list
	 *
	 * @param int    $per_page Items per page.
	 * @param string $qtxt Initial query text.
	 * @param int    $catid Initial category ID.
	 * @param string $nonce Nonce for AJAX requests.
	 * @param int    $max_pages Maximum number of pages.
	 */
	private function enqueue_assets( $per_page, $qtxt, $catid, $nonce, $max_pages ) {
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$version = TMW_Config::VERSION;

		// Enqueue jQuery (WordPress core)
		wp_enqueue_script( 'jquery' );

		// Enqueue JavaScript
		wp_enqueue_script(
			'tmw-infinite-scroll',
			$plugin_url . 'public/assets/js/infinite-scroll.js',
			array( 'jquery' ),
			$version,
			true
		);

		// Localize script with data
		wp_localize_script(
			'tmw-infinite-scroll',
			'tmwProductList',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => $nonce,
				'perPage'      => $per_page,
				'initialQuery' => $qtxt,
				'initialCat'   => $catid,
				'maxPages'     => $max_pages,
				'currentPage'  => max( 1, intval( $_GET['paged'] ?? 1 ) ),
			)
		);
	}
}
