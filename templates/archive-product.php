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

// Add search meta query if search query is present
if ( ! empty( $search_query ) ) {
	if ( $search_field === 'all' ) {
		// Search all fields
		$args['meta_query'] = TMW_Product_Fields::build_search_meta_query( $search_query );
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
$max_pages = $query->max_num_pages;
$total_products = $query->found_posts;

// Enqueue assets
$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
$version = TMW_Config::VERSION;

wp_enqueue_style(
	'tmw-product-list',
	$plugin_url . 'public/assets/css/product-list.css',
	array(),
	$version
);

wp_enqueue_script(
	'tmw-infinite-scroll',
	$plugin_url . 'public/assets/js/infinite-scroll.js',
	array( 'jquery' ),
	$version,
	true
);

?>

<div class="tmw-wrapper">
	<h1 class="tmw-archive-title">Products</h1>

	<div class="tmw-content">
		<!-- Search Form -->
		<div class="tmw-filter-form">
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
		</div>

		<!-- Product Table -->
		<div class="tmw-table-container">
		<table class="tmw-table">
			<thead>
				<tr>
					<th>Product Name</th>
					<th>SKU</th>
					<th>Vendor</th>
					<th>Vendor SKU</th>
					<th>Type</th>
					<th>Configuration</th>
					<th>Detail</th>
					<th>Alt Vendor</th>
					<th>Alt Vendor SKU</th>
					<th>Launch Date</th>
					<th>Owner</th>
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
						$sku            = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_SKU );
						$vendor         = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_VENDOR );
						$vendor_sku     = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_VENDOR_SKU );
						$type           = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_TYPE );
						$configuration  = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_CONFIG );
						$detail         = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_DETAIL );
						$alt_vendor     = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_ALT_VENDOR );
						$alt_vendor_sku = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_ALT_VENDOR_SKU );
						$launch         = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_LAUNCH );
						$owner          = TMW_Product_Fields::get_field( $post_id, TMW_Config::FIELD_OWNER );

						// Convert owner ID to name if numeric
						if ( $owner && is_numeric( $owner ) ) {
							$user = get_user_by( 'id', (int) $owner );
							$owner = $user ? $user->display_name : $owner;
						}
						?>
						<tr class="tmw-row" id="product-<?php echo esc_attr( $post_slug ); ?>">
							<td>
								<a href="<?php the_permalink(); ?>">
									<?php the_title(); ?>
								</a>
							</td>
							<td class="tmw-col-sku"><?php echo esc_html( $sku ); ?></td>
							<td><?php echo esc_html( $vendor ); ?></td>
							<td><?php echo esc_html( $vendor_sku ); ?></td>
							<td><?php echo esc_html( $type ); ?></td>
							<td><?php echo esc_html( $configuration ); ?></td>
							<td><?php echo esc_html( $detail ); ?></td>
							<td><?php echo esc_html( $alt_vendor ); ?></td>
							<td><?php echo esc_html( $alt_vendor_sku ); ?></td>
							<td><?php echo esc_html( $launch ); ?></td>
							<td><?php echo esc_html( $owner ); ?></td>
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
	</div>
</div>

<?php get_footer(); ?>
