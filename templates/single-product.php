<?php
/**
 * Single Product Template
 *
 * Displays a single product with specifications table and edit functionality.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if we're in edit mode BEFORE headers are sent
$is_edit_mode = false;
if ( have_posts() ) {
	global $post;
	$post = get_post( get_queried_object_id() );
	if ( $post ) {
		$is_edit_mode = isset( $_GET['edit'] ) && $_GET['edit'] === 'true' && is_user_logged_in() && current_user_can( 'edit_post', $post->ID );

		// Initialize ACF form head BEFORE get_header()
		if ( $is_edit_mode && function_exists( 'acf_form_head' ) ) {
			acf_form_head();
		}
	}
}

get_header();

while ( have_posts() ) :
	the_post();
	$id = get_the_ID();

	// Enqueue assets
	$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
	$version = TMW_Config::VERSION;

	if ( $is_edit_mode ) {
		// Enqueue form styles for edit mode
		wp_enqueue_style(
			'tmw-product-form',
			$plugin_url . 'public/assets/css/product-form.css',
			array(),
			$version
		);
	} else {
		// Enqueue single product styles for view mode
		wp_enqueue_style(
			'tmw-single-product',
			$plugin_url . 'public/assets/css/single-product.css',
			array(),
			$version
		);
	}

	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'tmw-single-product' ); ?>>

		<?php
		// Always go back to products archive with anchor link to this product
		$post_slug = get_post_field( 'post_name', $id );
		$archive_url = get_post_type_archive_link( TMW_Config::POST_TYPE );
		$back_url = $archive_url . '#product-' . $post_slug;
		?>

		<!-- Navigation Actions -->
		<div class="tmw-actions">
			<a href="<?php echo esc_url( $back_url ); ?>" class="tmw-btn tmw-btn-back">
				← Back to Products
			</a>

			<?php if ( is_user_logged_in() && current_user_can( 'edit_post', $id ) && ! $is_edit_mode ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'edit', 'true', get_permalink( $id ) ) ); ?>" class="tmw-btn tmw-btn-edit">
					Edit Product
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $is_edit_mode ) : ?>
			<!-- Edit Mode: ACF Form -->
			<div class="tmw-acf-form">
				<h1>Edit Product: <?php the_title(); ?></h1>

				<?php
				if ( function_exists( 'acf_form' ) ) {
					acf_form(
						array(
							'post_id'      => $id,
							'submit_value' => 'Update Product',
							'return'       => get_permalink( $id ), // Return to view mode after save
							'uploader'     => 'wp',
						)
					);
				} else {
					echo '<p>ACF Pro is not active.</p>';
				}
				?>
			</div>

		<?php else : ?>
			<!-- View Mode: Product Details -->
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
			</header>

			<div class="tmw-specs">
				<table class="tmw-specs-table">
					<tbody>
						<?php
						// Define fields to display (using actual ACF fields from reference.json)
						$fields = array(
							array(
								'label' => 'Internal SKU',
								'key'   => TMW_Config::FIELD_SKU,
							),
							array(
								'label' => 'Type',
								'key'   => TMW_Config::FIELD_TYPE,
							),
							array(
								'label' => 'Configuration',
								'key'   => TMW_Config::FIELD_CONFIG,
							),
							array(
								'label' => 'Detail',
								'key'   => TMW_Config::FIELD_DETAIL,
							),
							array(
								'label' => 'Vendor Name',
								'key'   => TMW_Config::FIELD_VENDOR,
							),
							array(
								'label' => 'Vendor SKU',
								'key'   => TMW_Config::FIELD_VENDOR_SKU,
							),
							array(
								'label' => 'Alternate Vendor',
								'key'   => TMW_Config::FIELD_ALT_VENDOR,
							),
							array(
								'label' => 'Alternate Vendor SKU',
								'key'   => TMW_Config::FIELD_ALT_VENDOR_SKU,
							),
							array(
								'label' => 'Launch Date',
								'key'   => TMW_Config::FIELD_LAUNCH,
							),
							array(
								'label' => 'Product URL',
								'key'   => TMW_Config::FIELD_URL,
							),
							array(
								'label' => 'Product Owner',
								'key'   => TMW_Config::FIELD_OWNER,
							),
						);

						// Render each field
						foreach ( $fields as $field ) {
							$value = TMW_Product_Fields::get_formatted_field( $id, $field['key'] );

							if ( ! empty( $value ) && $value !== '—' ) :
								?>
								<tr>
									<th><?php echo esc_html( $field['label'] ); ?></th>
									<td><?php echo wp_kses_post( $value ); ?></td>
								</tr>
								<?php
							endif;
						}

						// Categories
						$categories = TMW_Product_Fields::get_categories( $id );
						if ( $categories !== '—' ) :
							?>
							<tr>
								<th>Categories</th>
								<td><?php echo wp_kses_post( $categories ); ?></td>
							</tr>
							<?php
						endif;
						?>
					</tbody>
				</table>
			</div>

			<?php
			// Display post content if it exists
			if ( get_the_content() ) :
				?>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
				<?php
			endif;
			?>

		<?php endif; ?>

	</article>

	<?php
endwhile;

get_footer();
?>
