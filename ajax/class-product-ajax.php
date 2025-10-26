<?php
/**
 * Product AJAX Handler
 *
 * Handles AJAX requests for loading product list rows (infinite scroll).
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Product_Ajax class
 *
 * Handles AJAX endpoints for product listing.
 */
class TMW_Product_Ajax {

	/**
	 * Initialize the class and set up hooks
	 */
	public function init() {
		// AJAX for logged-in users
		add_action( 'wp_ajax_' . TMW_Config::AJAX_LOAD_PRODUCTS, array( $this, 'load_products' ) );

		// AJAX for non-logged-in users (public access)
		add_action( 'wp_ajax_nopriv_' . TMW_Config::AJAX_LOAD_PRODUCTS, array( $this, 'load_products' ) );
	}

	/**
	 * AJAX handler: Load product rows for infinite scroll/search
	 *
	 * Reads paging/search parameters from POST and returns HTML table rows.
	 */
	public function load_products() {
		// Verify nonce
		check_ajax_referer( TMW_Config::NONCE_PRODUCTS );

		// Get and sanitize POST parameters
		$paged    = max( 1, intval( $_POST['page'] ?? 1 ) );
		$per_page = min( 500, max( 1, intval( $_POST['per_page'] ?? 50 ) ) );
		$qtxt     = sanitize_text_field( $_POST['q'] ?? '' );
		$cat      = sanitize_text_field( $_POST['cat'] ?? '' );

		// Build query arguments
		$args = array(
			'post_type'      => TMW_Config::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			's'              => $qtxt,
		);

		// Add meta query for search across all fields
		if ( $qtxt ) {
			$args['meta_query'] = TMW_Product_Fields::build_search_meta_query( $qtxt );
		}

		// Add category filter if provided (accept both slug and ID)
		if ( $cat ) {
			$field = is_numeric( $cat ) ? 'term_id' : 'slug';
			$args['tax_query'] = array(
				array(
					'taxonomy' => TMW_Config::CATEGORY_TAX,
					'field'    => $field,
					'terms'    => $cat,
				),
			);
		}

		// Execute query
		$q = new WP_Query( $args );
		$rows = '';

		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				$rows .= $this->render_product_row( get_the_ID() );
			}
			wp_reset_postdata();
		}

		// Send JSON response
		wp_send_json_success(
			array(
				'rows'     => $rows,
				'has_more' => ( $q->max_num_pages > $paged ),
			)
		);
	}

	/**
	 * Render a single product table row
	 *
	 * @param int $post_id Product post ID.
	 * @return string HTML table row
	 */
	private function render_product_row( $post_id ) {
		// Get all field values
		$sku            = get_post_meta( $post_id, TMW_Config::FIELD_SKU, true );
		$vendor         = get_post_meta( $post_id, TMW_Config::FIELD_VENDOR, true );
		$vendor_sku     = get_post_meta( $post_id, TMW_Config::FIELD_VENDOR_SKU, true );
		$type           = get_post_meta( $post_id, TMW_Config::FIELD_TYPE, true );
		$configuration  = get_post_meta( $post_id, TMW_Config::FIELD_CONFIG, true );
		$detail         = get_post_meta( $post_id, TMW_Config::FIELD_DETAIL, true );
		$alt_vendor     = get_post_meta( $post_id, TMW_Config::FIELD_ALT_VENDOR, true );
		$alt_vendor_sku = get_post_meta( $post_id, TMW_Config::FIELD_ALT_VENDOR_SKU, true );
		$launch         = get_post_meta( $post_id, TMW_Config::FIELD_LAUNCH, true );
		$owner          = get_post_meta( $post_id, TMW_Config::FIELD_OWNER, true );

		// Convert owner ID to name if numeric
		if ( $owner && is_numeric( $owner ) ) {
			$user = get_user_by( 'id', (int) $owner );
			$owner = $user ? $user->display_name : $owner;
		}

		// Create a unique ID from post slug for anchor linking
		$post_slug = get_post_field( 'post_name', $post_id );

		$row = '<tr class="tmw-row" id="product-' . esc_attr( $post_slug ) . '">';
		$row .= '<td><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></td>';
		$row .= '<td class="tmw-col-sku">' . esc_html( $sku ) . '</td>';
		$row .= '<td>' . esc_html( $vendor ) . '</td>';
		$row .= '<td>' . esc_html( $vendor_sku ) . '</td>';
		$row .= '<td>' . esc_html( $type ) . '</td>';
		$row .= '<td>' . esc_html( $configuration ) . '</td>';
		$row .= '<td>' . esc_html( $detail ) . '</td>';
		$row .= '<td>' . esc_html( $alt_vendor ) . '</td>';
		$row .= '<td>' . esc_html( $alt_vendor_sku ) . '</td>';
		$row .= '<td>' . esc_html( $launch ) . '</td>';
		$row .= '<td>' . esc_html( $owner ) . '</td>';
		$row .= '</tr>';

		return $row;
	}
}
