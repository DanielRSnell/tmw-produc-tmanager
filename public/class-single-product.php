<?php
/**
 * Single Product View Handler
 *
 * Customizes the single product post content display with detailed specifications.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Single_Product class
 *
 * Handles the single product view on the front-end.
 */
class TMW_Single_Product {

	/**
	 * Initialize the class and set up hooks
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'render_product_view' ) );
		add_action( 'template_redirect', array( $this, 'maybe_init_acf_form' ) );
	}

	/**
	 * Initialize ACF form head if in edit mode on single product
	 */
	public function maybe_init_acf_form() {
		if ( ! function_exists( 'acf_form_head' ) ) {
			return;
		}

		if ( ! is_singular( TMW_Config::POST_TYPE ) ) {
			return;
		}

		if ( isset( $_GET['edit'] ) && current_user_can( 'edit_post', get_the_ID() ) ) {
			acf_form_head();
		}
	}

	/**
	 * Filter the content for single product posts
	 *
	 * @param string $content Original post content.
	 * @return string Modified content
	 */
	public function render_product_view( $content ) {
		if ( ! is_singular( TMW_Config::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$id = get_the_ID();

		// Check if we're in edit mode
		$is_edit_mode = isset( $_GET['edit'] ) && is_user_logged_in() && current_user_can( 'edit_post', $id );

		// Enqueue styles
		$this->enqueue_assets( $is_edit_mode );

		// If in edit mode, show the ACF form
		if ( $is_edit_mode ) {
			return $this->render_edit_form( $id );
		}

		// Get all product fields
		$title    = get_the_title();
		$sku      = get_post_meta( $id, TMW_Config::FIELD_SKU, true );
		$vend     = get_post_meta( $id, TMW_Config::FIELD_VENDOR, true );
		$vend_sku = get_post_meta( $id, TMW_Config::FIELD_VENDOR_SKU, true );
		$type     = get_post_meta( $id, TMW_Config::FIELD_TYPE, true );
		$conf     = get_post_meta( $id, TMW_Config::FIELD_CONFIG, true );
		$detail   = get_post_meta( $id, TMW_Config::FIELD_DETAIL, true );
		$kws      = get_post_meta( $id, TMW_Config::FIELD_KEYWORDS, true );
		$alt_v    = get_post_meta( $id, TMW_Config::FIELD_ALT_VENDOR, true );
		$alt_vs   = get_post_meta( $id, TMW_Config::FIELD_ALT_VENDOR_SKU, true );
		$launch   = get_post_meta( $id, TMW_Config::FIELD_LAUNCH, true );
		$url      = get_post_meta( $id, TMW_Config::FIELD_URL, true );
		$owner    = get_post_meta( $id, TMW_Config::FIELD_OWNER, true );

		// Convert owner ID to display name
		if ( $owner && is_numeric( $owner ) ) {
			$u = get_user_by( 'id', (int) $owner );
			if ( $u ) {
				$owner = $u->display_name;
			}
		}

		// Get category terms
		$terms = get_the_terms( $id, TMW_Config::CATEGORY_TAX );
		$catn  = $terms && ! is_wp_error( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';

		// Edit capability
		$can_edit = is_user_logged_in() && current_user_can( 'edit_post', $id );

		// Get referrer URL and add anchor if coming from product list
		$back_url = wp_get_referer();
		$post_slug = get_post_field( 'post_name', $id );
		if ( $back_url && strpos( $back_url, 'tmw_products' ) !== false ) {
			// Add anchor to scroll to this product's row in the list
			$back_url = $back_url . '#product-' . $post_slug;
		}

		// Render the view
		ob_start();
		?>
		<div class="tmw-single">
			<?php if ( $back_url ) : ?>
				<div class="tmw-back">
					<a href="<?php echo esc_url( $back_url ); ?>" class="tmw-btn tmw-btn-back">← Back to Products</a>
				</div>
			<?php endif; ?>
			<h1><?php echo esc_html( $title ); ?></h1>
			<table class="tmw-specs">
				<tr>
					<th>SKU</th>
					<td><?php echo esc_html( $sku ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Category</th>
					<td><?php echo esc_html( $catn ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Vendor</th>
					<td><?php echo esc_html( $vend ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Vendor SKU</th>
					<td><?php echo esc_html( $vend_sku ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Alternate Vendor Name</th>
					<td><?php echo esc_html( $alt_v ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Alternate Vendor SKU</th>
					<td><?php echo esc_html( $alt_vs ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Type</th>
					<td><?php echo esc_html( $type ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Configuration</th>
					<td><?php echo esc_html( $conf ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Detail</th>
					<td><?php echo nl2br( esc_html( $detail ?: '—' ) ); ?></td>
				</tr>
				<tr>
					<th>Keywords</th>
					<td><?php echo esc_html( $kws ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Launch Date</th>
					<td><?php echo esc_html( $launch ?: '—' ); ?></td>
				</tr>
				<tr>
					<th>Product URL</th>
					<td>
						<?php
						if ( $url ) {
							echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a>';
						} else {
							echo '—';
						}
						?>
					</td>
				</tr>
				<tr>
					<th>Product Owner</th>
					<td><?php echo esc_html( $owner ?: '—' ); ?></td>
				</tr>
			</table>

			<?php if ( $can_edit ) : ?>
				<div class="tmw-actions">
					<a class="tmw-btn tmw-btn-primary" href="<?php echo esc_url( add_query_arg( 'edit', 'true' ) ); ?>">
						Edit Product
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the edit form for a product
	 *
	 * @param int $post_id Product post ID.
	 * @return string Form HTML
	 */
	private function render_edit_form( $post_id ) {
		if ( ! function_exists( 'acf_form' ) ) {
			return '<p>ACF Pro is not active.</p>';
		}

		// Get the clean product URL (without ?edit parameter)
		$return_url = get_permalink( $post_id );

		ob_start();
		?>
		<div class="tmw-acf-form tmw-single">
			<h3>Edit Product: <?php echo esc_html( get_the_title( $post_id ) ); ?></h3>

			<?php
			acf_form(
				array(
					'post_id'           => $post_id,
					'return'            => $return_url, // Return to clean product URL after save
					'field_groups'      => array(),
					'submit_value'      => 'Update Product',
					'uploader'          => 'wp',
					'updated_message'   => false, // Disable default message for cleaner transition
					'html_after_fields' => '<div class="acf-actions" style="margin-top:16px;"><a href="' . esc_url( $return_url ) . '" class="button">Cancel</a></div>',
				)
			);
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue assets for the single product view
	 *
	 * @param bool $is_edit_mode Whether we're in edit mode.
	 */
	private function enqueue_assets( $is_edit_mode = false ) {
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$version = TMW_Config::VERSION;

		// Enqueue CSS
		wp_enqueue_style(
			'tmw-single-product',
			$plugin_url . 'public/assets/css/single-product.css',
			array(),
			$version
		);

		// If in edit mode, also enqueue form CSS
		if ( $is_edit_mode ) {
			wp_enqueue_style(
				'tmw-product-form',
				$plugin_url . 'public/assets/css/product-form.css',
				array(),
				$version
			);
		}
	}
}
