<?php
/**
 * Admin Columns Handler
 *
 * Customizes the admin list table for products with custom columns,
 * sorting, search, and row actions.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Admin_Columns class
 *
 * Handles admin list table customizations for the product post type.
 */
class TMW_Admin_Columns {

	/**
	 * Initialize the class and set up hooks
	 */
	public function init() {
		// Custom columns
		add_filter( 'manage_edit-' . TMW_Config::POST_TYPE . '_columns', array( $this, 'set_columns' ), 99 );
		add_action( 'manage_' . TMW_Config::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );

		// Sortable columns
		add_filter( 'manage_edit-' . TMW_Config::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting_and_search' ) );

		// Row actions (Copy SKU)
		add_filter( 'post_row_actions', array( $this, 'add_copy_sku_action' ), 10, 2 );

		// Enqueue admin scripts
		add_action( 'admin_footer-edit.php', array( $this, 'enqueue_clipboard_script' ) );

		// Show all columns by default
		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );
	}

	/**
	 * Define custom columns for the product list table
	 *
	 * @param array $cols Existing columns.
	 * @return array Modified columns
	 */
	public function set_columns( $cols ) {
		return array(
			'cb'                                   => '<input type="checkbox" />',
			'title'                                => __( 'Title' ),
			'tmw_sku'                              => __( 'SKU', 'tmw' ),
			'taxonomy-' . TMW_Config::CATEGORY_TAX => __( 'Category', 'tmw' ),
			'tmw_vendor'                           => __( 'Vendor', 'tmw' ),
			'tmw_vendor_sku'                       => __( 'Vendor SKU', 'tmw' ),
			'tmw_type'                             => __( 'Type', 'tmw' ),
			'tmw_config'                           => __( 'Configuration', 'tmw' ),
		);
	}

	/**
	 * Output content for custom columns
	 *
	 * @param string $col Column name.
	 * @param int    $post_id Post ID.
	 */
	public function column_content( $col, $post_id ) {
		switch ( $col ) {
			case 'tmw_sku':
				echo esc_html( get_post_meta( $post_id, TMW_Config::FIELD_SKU, true ) );
				break;
			case 'tmw_vendor':
				echo esc_html( get_post_meta( $post_id, TMW_Config::FIELD_VENDOR, true ) );
				break;
			case 'tmw_vendor_sku':
				echo esc_html( get_post_meta( $post_id, TMW_Config::FIELD_VENDOR_SKU, true ) );
				break;
			case 'tmw_type':
				echo esc_html( get_post_meta( $post_id, TMW_Config::FIELD_TYPE, true ) );
				break;
			case 'tmw_config':
				echo esc_html( get_post_meta( $post_id, TMW_Config::FIELD_CONFIG, true ) );
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @param array $cols Existing sortable columns.
	 * @return array Modified sortable columns
	 */
	public function sortable_columns( $cols ) {
		$cols['tmw_sku']    = 'tmw_sku';
		$cols['tmw_type']   = 'tmw_type';
		$cols['tmw_vendor'] = 'tmw_vendor';
		return $cols;
	}

	/**
	 * Handle sorting and extended search for admin list
	 *
	 * @param WP_Query $q The WP_Query instance.
	 */
	public function handle_sorting_and_search( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->get( 'post_type' ) !== TMW_Config::POST_TYPE ) {
			return;
		}

		// Handle sorting
		$orderby = $q->get( 'orderby' );
		if ( $orderby === 'tmw_sku' ) {
			$q->set( 'meta_key', TMW_Config::FIELD_SKU );
			$q->set( 'orderby', 'meta_value' );
		}
		if ( $orderby === 'tmw_type' ) {
			$q->set( 'meta_key', TMW_Config::FIELD_TYPE );
			$q->set( 'orderby', 'meta_value' );
		}
		if ( $orderby === 'tmw_vendor' ) {
			$q->set( 'meta_key', TMW_Config::FIELD_VENDOR );
			$q->set( 'orderby', 'meta_value' );
		}

		// Extend admin search to all product metadata fields
		$search_query = $q->get( 's' );
		if ( $search_query ) {
			$q->set( 'meta_query', TMW_Product_Fields::build_search_meta_query( $search_query ) );
		}
	}

	/**
	 * Add "Copy SKU" row action
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post Current post object.
	 * @return array Modified actions
	 */
	public function add_copy_sku_action( $actions, $post ) {
		if ( $post->post_type !== TMW_Config::POST_TYPE ) {
			return $actions;
		}

		$sku = get_post_meta( $post->ID, TMW_Config::FIELD_SKU, true );
		if ( $sku ) {
			$actions['tmw_copy_sku'] = sprintf(
				'<a href="#" class="tmw-copy-sku" data-sku="%s">%s</a>',
				esc_attr( $sku ),
				__( 'Copy SKU', 'tmw' )
			);
		}

		return $actions;
	}

	/**
	 * Enqueue clipboard JavaScript for Copy SKU functionality
	 */
	public function enqueue_clipboard_script() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== TMW_Config::POST_TYPE ) {
			return;
		}

		$script_url = plugin_dir_url( dirname( __FILE__ ) ) . 'admin/assets/js/admin-clipboard.js';
		$version = TMW_Config::VERSION;

		wp_enqueue_script( 'tmw-admin-clipboard', $script_url, array(), $version, true );
	}

	/**
	 * Show all columns by default (none hidden)
	 *
	 * @param array     $hidden Hidden columns.
	 * @param WP_Screen $screen Current screen.
	 * @return array Modified hidden columns
	 */
	public function default_hidden_columns( $hidden, $screen ) {
		if ( $screen->id === 'edit-' . TMW_Config::POST_TYPE ) {
			return array();
		}
		return $hidden;
	}
}
