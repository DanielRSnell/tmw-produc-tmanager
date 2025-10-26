<?php
/**
 * Plugin Name: TMW Products Manager
 * Description: Products admin enhancements + front-end searchable list with infinite scroll + ACF form for add/edit + custom single Product view.
 * Version: 2.0.8
 * Author: Texas Metal Works
 * License: GPL2+
 * Text Domain: tmw
 *
 * @package TMW_Product_Manager
 */

/*
===============================================================================
 Texas Metal Works — TMW Products Manager
-------------------------------------------------------------------------------

 Quick map of major pieces in this plugin:

 - CPT/Tax usage: This plugin assumes a CPT `product` and taxonomy `product_category`
   registered by CPT UI (or ACF). ACF Pro provides custom fields for product details.

 - Shortcode [tmw_products]: Renders the front-end product list UI (search input,
   results table, and the infinite scroll JS). It triggers an AJAX loader to fetch
   more rows.

 - AJAX action `tmw_products_load`: Returns rows of `product` posts as HTML <tr>'s.
   This powers both the first filtered page (after you hit Search) and subsequent
   pages as the infinite scroll requests more.

 - Shortcode [tmw_product_form]: Renders an ACF front-end form for create/edit
   operations (post type: product). This uses `acf_form()` and respects user caps.

 - Single product view: Outputs a details/spec table for one product using values
   from ACF fields and taxonomy terms.

 Version 2.0.0 Changes:
 - Refactored into modular structure with separate classes
 - Moved inline CSS/JS to external files for better caching
 - Improved code organization and maintainability
 - All functionality remains the same, just better organized

===============================================================================
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
if ( ! defined( 'TMW_PLUGIN_FILE' ) ) {
	define( 'TMW_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'TMW_PLUGIN_DIR' ) ) {
	define( 'TMW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TMW_PLUGIN_URL' ) ) {
	define( 'TMW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load the main plugin class and initialize
 */
function tmw_product_manager_init() {
	// Load main plugin class
	require_once TMW_PLUGIN_DIR . 'includes/class-plugin.php';

	// Initialize plugin
	TMW_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'tmw_product_manager_init' );

/**
 * Plugin activation hook
 */
function tmw_product_manager_activate() {
	require_once TMW_PLUGIN_DIR . 'includes/class-plugin.php';
	TMW_Plugin::activate();
}
register_activation_hook( __FILE__, 'tmw_product_manager_activate' );

/**
 * Plugin deactivation hook
 */
function tmw_product_manager_deactivate() {
	require_once TMW_PLUGIN_DIR . 'includes/class-plugin.php';
	TMW_Plugin::deactivate();
}
register_deactivation_hook( __FILE__, 'tmw_product_manager_deactivate' );
