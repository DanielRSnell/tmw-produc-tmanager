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

		// Add search query
		if ( $qtxt ) {
			$args['s'] = $qtxt;
			$args['meta_query'] = TMW_Product_Fields::build_search_meta_query( $qtxt );
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

		// Enqueue assets
		$this->enqueue_assets( $per_page, $qtxt, $catid, $nonce, $query->max_num_pages );

		// Render HTML
		ob_start();
		?>
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

		// Enqueue CSS
		wp_enqueue_style(
			'tmw-product-list',
			$plugin_url . 'public/assets/css/product-list.css',
			array(),
			$version
		);

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
