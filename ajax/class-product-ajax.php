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
	 * Current search query
	 *
	 * @var string
	 */
	private $current_search_query = '';

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

		// Remove search filters after query
		if ( $qtxt ) {
			remove_filter( 'posts_search', array( $this, 'custom_search_filter' ), 10 );
			remove_filter( 'posts_join', array( $this, 'custom_search_join' ), 10 );
			remove_filter( 'posts_groupby', array( $this, 'custom_search_groupby' ), 10 );
			$this->current_search_query = '';
		}

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
