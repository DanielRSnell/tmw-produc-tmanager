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

	?>

	<?php if ( $is_edit_mode ) : ?>
		<style>
		@view-transition {
			navigation: auto;
		}

		:root {
			--global-palette9: #f8fafc;
		}

		.inner-wrap {
			background: #f8fafc !important;
		}

		.tmw-single-product {
			max-width: 80rem;
			margin-left: auto;
			margin-right: auto;
			padding-top: 2rem;
			padding-bottom: 2rem;
		}

		.tmw-acf-form .acf-form {
			background: #fff;
			border: 1px solid #e8e8e8;
			border-radius: 12px;
			padding: 20px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
		}

		.tmw-acf-form .acf-fields {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
		}

		.tmw-acf-form .acf-field {
			flex: 1 1 100%;
			margin: 0;
		}

		.tmw-acf-form .acf-field[data-name="internal_sku"],
		.tmw-acf-form .acf-field[data-name="launch_date"],
		.tmw-acf-form .acf-field[data-name="product_owner"] {
			order: 1;
			flex: 1 1 calc(33.33% - 12px);
		}

		.tmw-acf-form .acf-field[data-name="product_category"],
		.tmw-acf-form .acf-field[data-name="type"],
		.tmw-acf-form .acf-field[data-name="configuration"] {
			order: 2;
			flex: 1 1 calc(33.33% - 12px);
		}

		.tmw-acf-form .acf-field[data-name="configuration"] textarea {
			min-height: 34px;
			height: 34px;
			resize: vertical;
		}

		.tmw-acf-form .acf-field[data-name="detail"] {
			order: 3;
			flex-basis: 100%;
		}

		.tmw-acf-form .acf-field[data-name="detail"] textarea {
			min-height: 48px;
		}

		.tmw-acf-form .acf-field[data-name="vendor_name"],
		.tmw-acf-form .acf-field[data-name="vendor_sku"] {
			order: 4;
			flex: 1 1 calc(50% - 12px);
		}

		.tmw-acf-form .acf-field[data-name="alternate_vendor_name"],
		.tmw-acf-form .acf-field[data-name="alternate_vendor_sku"] {
			order: 5;
			flex: 1 1 calc(50% - 12px);
		}

		.tmw-acf-form .acf-field[data-name="keywords_raw"],
		.tmw-acf-form .acf-field[data-name="product_url"] {
			order: 6;
			flex: 1 1 calc(50% - 12px);
		}

		.tmw-acf-form .acf-label label {
			font-size: 12px;
			color: #555;
			margin-bottom: 4px;
		}

		.tmw-acf-form .acf-input input,
		.tmw-acf-form .acf-input select {
			height: 34px;
			padding: 6px;
		}

		.tmw-acf-form .acf-input textarea {
			min-height: 80px;
		}

		.tmw-actions {
			margin-bottom: 24px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 10px;
		}

		.tmw-btn {
			padding: 8px 12px;
			border-radius: 6px;
			text-decoration: none;
			display: inline-block;
			transition: all 0.2s ease;
		}

		.tmw-btn-back {
			background: #f0f0f0;
			color: #333;
			font-size: 14px;
		}

		.tmw-btn-back:hover {
			background: #e0e0e0;
		}

		.tmw-btn-edit {
			background: #1e73be;
			color: #fff;
			font-size: 14px;
		}

		.tmw-btn-edit:hover {
			background: #155a8a;
		}
		</style>
	<?php else : ?>
		<style>
		@view-transition {
			navigation: auto;
		}

		:root {
			--global-palette9: #f8fafc;
		}

		.inner-wrap {
			background: #f8fafc !important;
		}

		.tmw-single-product {
			max-width: 80rem;
			margin-left: auto;
			margin-right: auto;
			padding-top: 2rem;
			padding-bottom: 2rem;
		}

		.tmw-single h1 {
			margin-top: 0;
		}

		.tmw-specs {
			width: 100%;
			border-collapse: collapse;
			margin-top: 16px;
			background: #fff;
			border: 1px solid #e8e8e8;
			border-radius: 12px;
			padding: 20px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
		}

		.tmw-specs th,
		.tmw-specs td {
			padding: 10px;
			border-bottom: 1px solid #f0f0f0;
			text-align: left;
			vertical-align: top;
		}

		.tmw-specs th {
			width: 220px;
			color: #555;
			background: #fafafa;
		}

		.tmw-actions {
			margin-bottom: 24px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 10px;
		}

		.tmw-btn {
			padding: 8px 12px;
			border-radius: 6px;
			text-decoration: none;
			display: inline-block;
			transition: all 0.2s ease;
		}

		.tmw-btn-primary {
			background: #1e73be;
			color: #fff;
		}

		.tmw-btn-primary:hover {
			background: #155a8a;
		}

		.tmw-btn-back {
			background: #f0f0f0;
			color: #333;
			font-size: 14px;
		}

		.tmw-btn-back:hover {
			background: #e0e0e0;
		}

		.tmw-btn-edit {
			background: #1e73be;
			color: #fff;
			font-size: 14px;
		}

		.tmw-btn-edit:hover {
			background: #155a8a;
		}
		</style>
	<?php endif; ?>

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

						// Render each field (show all fields, even if empty)
						foreach ( $fields as $field ) {
							$value = TMW_Product_Fields::get_formatted_field( $id, $field['key'] );
							?>
							<tr>
								<th><?php echo esc_html( $field['label'] ); ?></th>
								<td><?php echo wp_kses_post( $value ); ?></td>
							</tr>
							<?php
						}

						// Categories (always show)
						$categories = TMW_Product_Fields::get_categories( $id );
						?>
						<tr>
							<th>Categories</th>
							<td><?php echo wp_kses_post( $categories ); ?></td>
						</tr>
						<?php
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
