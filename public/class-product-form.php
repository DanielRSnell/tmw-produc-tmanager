<?php
/**
 * Product Form Shortcode Handler
 *
 * Renders the [tmw_product_form] ACF front-end form for add/edit operations.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Product_Form class
 *
 * Handles the front-end ACF form shortcode.
 */
class TMW_Product_Form {

	/**
	 * Initialize the class and set up hooks
	 */
	public function init() {
		add_shortcode( 'tmw_product_form', array( $this, 'render_shortcode' ) );
		add_action( 'template_redirect', array( $this, 'acf_form_head' ) );
	}

	/**
	 * Initialize ACF form head when shortcode is present
	 */
	public function acf_form_head() {
		if ( ! function_exists( 'acf_form_head' ) ) {
			return;
		}
		if ( ! is_page() ) {
			return;
		}

		$content = get_post_field( 'post_content', get_queried_object_id() );
		if ( $content && has_shortcode( $content, 'tmw_product_form' ) ) {
			acf_form_head();
		}
	}

	/**
	 * Render the [tmw_product_form] shortcode
	 *
	 * @return string Shortcode output
	 */
	public function render_shortcode() {
		if ( ! function_exists( 'acf_form' ) ) {
			return '<p>ACF Pro is not active.</p>';
		}

		if ( ! is_user_logged_in() ) {
			return '<p>Please log in to submit a product.</p>';
		}

		$edit_id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
		$post_id = 'new_post';

		if ( $edit_id && get_post_type( $edit_id ) === TMW_Config::POST_TYPE && current_user_can( 'edit_post', $edit_id ) ) {
			$post_id = $edit_id; // edit mode
		}

		// Enqueue form styles
		$this->enqueue_assets();

		ob_start();
		?>
		<div class="tmw-acf-form">
			<?php if ( $post_id === 'new_post' ) : ?>
				<h3>Add Product</h3>
			<?php else : ?>
				<h3>Edit Product: <?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			<?php endif; ?>

			<?php
			acf_form(
				array(
					'post_id'      => $post_id,
					'new_post'     => array(
						'post_type'   => TMW_Config::POST_TYPE,
						'post_status' => 'publish',
					),
					'field_groups' => array(),
					'submit_value' => ( $post_id === 'new_post' ) ? 'Save Product' : 'Update Product',
					'uploader'     => 'wp',
				)
			);
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue assets for the product form
	 */
	private function enqueue_assets() {
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$version = TMW_Config::VERSION;

		// Enqueue CSS
		wp_enqueue_style(
			'tmw-product-form',
			$plugin_url . 'public/assets/css/product-form.css',
			array(),
			$version
		);
	}
}
