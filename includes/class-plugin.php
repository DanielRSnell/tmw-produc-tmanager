<?php
/**
 * Main Plugin Class
 *
 * Orchestrates all plugin components and initializes the plugin.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Plugin class
 *
 * Main plugin orchestrator that initializes all components.
 */
class TMW_Plugin {

	/**
	 * Plugin instance (singleton)
	 *
	 * @var TMW_Plugin
	 */
	private static $instance = null;

	/**
	 * Component instances
	 *
	 * @var array
	 */
	private $components = array();

	/**
	 * Get plugin instance (singleton pattern)
	 *
	 * @return TMW_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton pattern)
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_components();
	}

	/**
	 * Load all required class files
	 */
	private function load_dependencies() {
		$plugin_dir = plugin_dir_path( dirname( __FILE__ ) );

		// Core classes
		require_once $plugin_dir . 'includes/class-config.php';
		require_once $plugin_dir . 'includes/class-product-fields.php';

		// Admin classes
		require_once $plugin_dir . 'admin/class-admin-columns.php';

		// AJAX classes
		require_once $plugin_dir . 'ajax/class-product-ajax.php';

		// Public classes (only form for backward compatibility)
		require_once $plugin_dir . 'public/class-product-form.php';
	}

	/**
	 * Initialize all plugin components
	 */
	private function init_components() {
		// Admin components (only in admin)
		if ( is_admin() ) {
			$this->components['admin_columns'] = new TMW_Admin_Columns();
			$this->components['admin_columns']->init();
		}

		// AJAX components (both admin and front-end)
		$this->components['product_ajax'] = new TMW_Product_Ajax();
		$this->components['product_ajax']->init();

		// Template loader for archive and single views
		add_filter( 'template_include', array( $this, 'load_custom_templates' ) );

		// Keep shortcode support for backward compatibility
		$this->components['product_form'] = new TMW_Product_Form();
		$this->components['product_form']->init();
	}

	/**
	 * Load custom templates for product archive and single views
	 *
	 * @param string $template Path to template file.
	 * @return string Modified template path
	 */
	public function load_custom_templates( $template ) {
		// Check if we're viewing the product post type
		if ( ! is_post_type_archive( TMW_Config::POST_TYPE ) && ! is_singular( TMW_Config::POST_TYPE ) ) {
			return $template;
		}

		$plugin_dir = plugin_dir_path( dirname( __FILE__ ) );

		// Archive template
		if ( is_post_type_archive( TMW_Config::POST_TYPE ) ) {
			$custom_template = $plugin_dir . 'templates/archive-product.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		// Single template
		if ( is_singular( TMW_Config::POST_TYPE ) ) {
			$custom_template = $plugin_dir . 'templates/single-product.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		return $template;
	}

	/**
	 * Get a specific component instance
	 *
	 * @param string $component_name Component name.
	 * @return object|null Component instance or null if not found
	 */
	public function get_component( $component_name ) {
		return isset( $this->components[ $component_name ] ) ? $this->components[ $component_name ] : null;
	}

	/**
	 * Plugin activation hook
	 */
	public static function activate() {
		// Flush rewrite rules if needed
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
