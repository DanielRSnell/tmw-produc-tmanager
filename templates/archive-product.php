<?php
/**
 * Archive Template for Products
 *
 * Displays the product listing table with search and infinite scroll.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom search filter for archive page - searches title OR meta fields
 */
function tmw_archive_custom_search_filter( $search, $query ) {
	global $wpdb, $tmw_archive_search_query;

	if ( empty( $tmw_archive_search_query ) ) {
		return $search;
	}

	$search_term = $wpdb->esc_like( $tmw_archive_search_query );
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
 */
function tmw_archive_custom_search_join( $join, $query ) {
	global $wpdb, $tmw_archive_search_query;

	if ( empty( $tmw_archive_search_query ) ) {
		return $join;
	}

	$join .= " LEFT JOIN {$wpdb->postmeta} AS pm ON ({$wpdb->posts}.ID = pm.post_id)";

	return $join;
}

/**
 * Group by post ID to prevent duplicate results
 */
function tmw_archive_custom_search_groupby( $groupby, $query ) {
	global $wpdb, $tmw_archive_search_query;

	if ( empty( $tmw_archive_search_query ) ) {
		return $groupby;
	}

	if ( ! empty( $groupby ) ) {
		$groupby .= ', ';
	}
	$groupby .= "{$wpdb->posts}.ID";

	return $groupby;
}

// Check if we're in edit mode (for creating new product)
$is_edit_mode = isset( $_GET['edit'] ) && $_GET['edit'] === 'true' && is_user_logged_in() && current_user_can( 'edit_posts' );

// Initialize ACF form head BEFORE get_header() if in edit mode
if ( $is_edit_mode && function_exists( 'acf_form_head' ) ) {
	acf_form_head();
}

get_header();

// Get current page and search query from URL
$paged        = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$search_query = isset( $_GET['tmw_search'] ) ? sanitize_text_field( $_GET['tmw_search'] ) : '';
$search_field = isset( $_GET['tmw_field'] ) ? sanitize_text_field( $_GET['tmw_field'] ) : 'all';
$per_page     = 20;

// Build query args
$args = array(
	'post_type'      => TMW_Config::POST_TYPE,
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
);

// Add search filters if search query is present
if ( ! empty( $search_query ) ) {
	if ( $search_field === 'all' ) {
		// Search all fields + title with custom filter
		add_filter( 'posts_search', 'tmw_archive_custom_search_filter', 10, 2 );
		add_filter( 'posts_join', 'tmw_archive_custom_search_join', 10, 2 );
		add_filter( 'posts_groupby', 'tmw_archive_custom_search_groupby', 10, 2 );

		// Store search query in global for filters
		global $tmw_archive_search_query;
		$tmw_archive_search_query = $search_query;
	} elseif ( $search_field === 'title' ) {
		// Search post title only
		$args['s'] = $search_query;
	} else {
		// Search specific field only
		$args['meta_query'] = array(
			array(
				'key'     => $search_field,
				'value'   => $search_query,
				'compare' => 'LIKE',
			),
		);
	}
}

// Execute query
$query = new WP_Query( $args );

// Remove search filters after query
if ( ! empty( $search_query ) && $search_field === 'all' ) {
	remove_filter( 'posts_search', 'tmw_archive_custom_search_filter', 10 );
	remove_filter( 'posts_join', 'tmw_archive_custom_search_join', 10 );
	remove_filter( 'posts_groupby', 'tmw_archive_custom_search_groupby', 10 );
	unset( $GLOBALS['tmw_archive_search_query'] );
}
$max_pages = $query->max_num_pages;
$total_products = $query->found_posts;

// Enqueue assets
$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
$version = TMW_Config::VERSION;

wp_enqueue_script(
	'tmw-infinite-scroll',
	$plugin_url . 'public/assets/js/infinite-scroll.js',
	array( 'jquery' ),
	$version,
	true
);

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
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 6px;
	font-size: 14px;
	height: 38px;
	line-height: 1.5;
	box-sizing: border-box;
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

.tmw-btn-search {
	padding: 8px 20px;
	background: #1e73be;
	color: #fff;
	border-radius: 6px;
	border: none;
	cursor: pointer;
	font-size: 14px;
	height: 38px;
	line-height: 1.5;
	transition: background 0.2s;
	box-sizing: border-box;
}

.tmw-btn-search:hover {
	background: #155a8a;
}

.tmw-btn-reset {
	padding: 8px 16px;
	background: #f5f5f5;
	color: #333;
	border-radius: 6px;
	text-decoration: none;
	border: 1px solid #ddd;
	transition: background 0.2s;
	height: 38px;
	line-height: 1.5;
	box-sizing: border-box;
	display: inline-flex;
	align-items: center;
	font-size: 14px;
}

.tmw-btn-reset:hover {
	background: #e8e8e8;
}

.tmw-archive-title {
	margin-top: 0;
	margin-bottom: 20px;
}

.tmw-btn-add-product {
	padding: 10px 20px;
	background: #1e73be;
	color: #fff;
	border-radius: 6px;
	text-decoration: none;
	font-size: 14px;
	font-weight: 600;
	transition: background 0.2s;
	display: inline-block;
}

.tmw-btn-add-product:hover {
	background: #155a8a;
	color: #fff;
}

.tmw-btn-back {
	padding: 8px 12px;
	background: #f0f0f0;
	color: #333;
	border-radius: 6px;
	text-decoration: none;
	display: inline-block;
	font-size: 14px;
	transition: background 0.2s;
}

.tmw-btn-back:hover {
	background: #e0e0e0;
	color: #333;
}

.tmw-add-product-form {
	max-width: 80rem;
	margin: 0 auto;
}

.tmw-add-product-form h2 {
	margin-top: 0;
	margin-bottom: 20px;
	color: #333;
}

.tmw-add-product-form .acf-form {
	background: #fff;
	border: 1px solid #e8e8e8;
	border-radius: 12px;
	padding: 20px;
	box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
}

.tmw-add-product-form .acf-fields {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
}

.tmw-add-product-form .acf-field {
	flex: 1 1 100%;
	margin: 0;
	padding: 0;
}

.tmw-add-product-form .acf-label label {
	font-size: 12px;
	color: #555;
	margin-bottom: 4px;
	font-weight: 600;
}

.tmw-add-product-form .acf-input input,
.tmw-add-product-form .acf-input select {
	height: 34px;
	padding: 6px;
	border: 1px solid #ddd;
	border-radius: 4px;
	width: 100%;
}

.tmw-add-product-form .acf-input textarea {
	min-height: 80px;
	padding: 6px;
	border: 1px solid #ddd;
	border-radius: 4px;
	width: 100%;
}

.tmw-add-product-form .acf-field[data-name="internal_sku"],
.tmw-add-product-form .acf-field[data-name="launch_date"],
.tmw-add-product-form .acf-field[data-name="product_owner"] {
	order: 1;
	flex: 1 1 calc(33.33% - 12px);
}

.tmw-add-product-form .acf-field[data-name="product_category"],
.tmw-add-product-form .acf-field[data-name="type"],
.tmw-add-product-form .acf-field[data-name="configuration"] {
	order: 2;
	flex: 1 1 calc(33.33% - 12px);
}

.tmw-add-product-form .acf-field[data-name="configuration"] textarea {
	min-height: 34px;
	height: 34px;
	resize: vertical;
}

.tmw-add-product-form .acf-field[data-name="detail"] {
	order: 3;
	flex-basis: 100%;
}

.tmw-add-product-form .acf-field[data-name="detail"] textarea {
	min-height: 48px;
}

.tmw-add-product-form .acf-field[data-name="vendor_name"],
.tmw-add-product-form .acf-field[data-name="vendor_sku"] {
	order: 4;
	flex: 1 1 calc(50% - 12px);
}

.tmw-add-product-form .acf-field[data-name="alternate_vendor_name"],
.tmw-add-product-form .acf-field[data-name="alternate_vendor_sku"] {
	order: 5;
	flex: 1 1 calc(50% - 12px);
}

.tmw-add-product-form .acf-field[data-name="keywords_raw"],
.tmw-add-product-form .acf-field[data-name="product_url"] {
	order: 6;
	flex: 1 1 calc(50% - 12px);
}

.tmw-add-product-form .acf-form-submit {
	margin-top: 20px;
}

.tmw-add-product-form .acf-form-submit input[type="submit"] {
	background: #1e73be;
	color: #fff;
	padding: 10px 20px;
	border: none;
	border-radius: 6px;
	font-size: 14px;
	cursor: pointer;
	transition: background 0.2s;
}

.tmw-add-product-form .acf-form-submit input[type="submit"]:hover {
	background: #155a8a;
}
</style>

<div class="tmw-wrapper">
	<?php if ( $is_edit_mode ) : ?>
		<!-- Edit Mode Header -->
		<div style="margin-bottom: 24px;">
			<a href="<?php echo esc_url( get_post_type_archive_link( TMW_Config::POST_TYPE ) ); ?>" class="tmw-btn-back">
				← Back to Products
			</a>
		</div>
	<?php else : ?>
		<!-- Normal Mode Header -->
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
			<h1 class="tmw-archive-title" style="margin: 0;">Products</h1>
			<?php if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'edit', 'true', get_post_type_archive_link( TMW_Config::POST_TYPE ) ) ); ?>" class="tmw-btn-add-product">
					+ Add Product
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="tmw-content">
		<?php if ( $is_edit_mode ) : ?>
			<!-- Add New Product Form -->
			<div class="tmw-add-product-form">
				<h2>Add New Product</h2>
				<?php
				if ( function_exists( 'acf_form' ) ) {
					acf_form(
						array(
							'post_id'      => 'new_post',
							'new_post'     => array(
								'post_type'   => TMW_Config::POST_TYPE,
								'post_status' => 'publish',
							),
							'post_title'   => true,
							'post_content' => false,
							'submit_value' => 'Create Product',
							'return'       => get_post_type_archive_link( TMW_Config::POST_TYPE ),
							'uploader'     => 'wp',
						)
					);
				} else {
					echo '<p>ACF Pro is not active.</p>';
				}
				?>
			</div>
		<?php else : ?>
		<!-- Search Form -->
		<form method="get" class="tmw-filter-form">
			<select name="tmw_field" class="tmw-field-select">
				<option value="all" <?php selected( $search_field, 'all' ); ?>>All Fields</option>
				<option value="title" <?php selected( $search_field, 'title' ); ?>>Product Name</option>
				<option value="internal_sku" <?php selected( $search_field, 'internal_sku' ); ?>>SKU</option>
				<option value="vendor_name" <?php selected( $search_field, 'vendor_name' ); ?>>Vendor</option>
				<option value="vendor_sku" <?php selected( $search_field, 'vendor_sku' ); ?>>Vendor SKU</option>
				<option value="type" <?php selected( $search_field, 'type' ); ?>>Type</option>
				<option value="configuration" <?php selected( $search_field, 'configuration' ); ?>>Configuration</option>
				<option value="detail" <?php selected( $search_field, 'detail' ); ?>>Detail</option>
				<option value="alternate_vendor_name" <?php selected( $search_field, 'alternate_vendor_name' ); ?>>Alt Vendor</option>
				<option value="alternate_vendor_sku" <?php selected( $search_field, 'alternate_vendor_sku' ); ?>>Alt Vendor SKU</option>
			</select>
			<input
				type="text"
				name="tmw_search"
				class="tmw-search-input"
				placeholder="Search products..."
				value="<?php echo esc_attr( $search_query ); ?>"
				autocomplete="off"
			>
			<button type="submit" class="tmw-btn-search">Search</button>
			<?php if ( ! empty( $search_query ) ) : ?>
				<a href="<?php echo esc_url( get_post_type_archive_link( TMW_Config::POST_TYPE ) ); ?>" class="tmw-btn-reset">Clear</a>
			<?php endif; ?>
		</form>

		<!-- Product Table -->
		<div class="tmw-table-container">
		<table class="tmw-table">
			<thead>
				<tr>
					<th>Product Name</th>
					<th>Category</th>
					<th>SKU</th>
					<th>Vendor</th>
					<th>Vendor SKU</th>
					<th>Type</th>
					<th>Configuration</th>
				</tr>
			</thead>
			<tbody id="tmw-results">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$post_id   = get_the_ID();
						$post_slug = get_post_field( 'post_name', $post_id );

						// Get field values
						$sku           = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_SKU );
						$vendor        = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_VENDOR );
						$vendor_sku    = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_VENDOR_SKU );
						$type          = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_TYPE );
						$configuration = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_CONFIG );

						// Get categories
						$terms = get_the_terms( $post_id, TMW_Config::CATEGORY_TAX );
						$category = ( $terms && ! is_wp_error( $terms ) ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';
						?>
						<tr class="tmw-row" id="product-<?php echo esc_attr( $post_slug ); ?>">
							<td>
								<a href="<?php the_permalink(); ?>">
									<?php the_title(); ?>
								</a>
							</td>
							<td><?php echo esc_html( $category ); ?></td>
							<td class="tmw-col-sku"><?php echo esc_html( $sku ); ?></td>
							<td><?php echo esc_html( $vendor ); ?></td>
							<td><?php echo esc_html( $vendor_sku ); ?></td>
							<td><?php echo esc_html( $type ); ?></td>
							<td><?php echo esc_html( $configuration ); ?></td>
						</tr>
						<?php
					endwhile;
					wp_reset_postdata();
				endif;
				?>
			</tbody>
		</table>

		<!-- Loading indicator for infinite scroll -->
		<div id="tmw-loader" class="tmw-loader" style="display:none;">
			Loading more products...
		</div>

		<!-- Product counter -->
		<div id="tmw-counter" class="tmw-counter" data-total="<?php echo esc_attr( $total_products ); ?>">
			Showing <span id="tmw-count"><?php echo esc_html( $query->post_count ); ?></span> of <?php echo esc_html( $total_products ); ?> products
		</div>

		<!-- Sentinel element for intersection observer -->
		<div id="tmw-sentinel" class="tmw-sentinel"></div>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>
