<?php
/**
 * Configuration class for TMW Product Manager
 *
 * Centralizes all plugin configuration constants and settings.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Config class
 *
 * Holds all plugin configuration as class constants and static methods.
 */
class TMW_Config {

	/**
	 * Post Type
	 */
	const POST_TYPE = 'product';

	/**
	 * Taxonomy
	 */
	const CATEGORY_TAX = 'product_category';

	/**
	 * Field Names (from ACF reference.json)
	 */
	const FIELD_SKU = 'internal_sku';
	const FIELD_VENDOR = 'vendor_name';
	const FIELD_VENDOR_SKU = 'vendor_sku';
	const FIELD_TYPE = 'type';
	const FIELD_CONFIG = 'configuration';
	const FIELD_DETAIL = 'detail';
	const FIELD_KEYWORDS = 'keywords_raw';
	const FIELD_ALT_VENDOR = 'alternate_vendor_name';
	const FIELD_ALT_VENDOR_SKU = 'alternate_vendor_sku';
	const FIELD_LAUNCH = 'launch_date';
	const FIELD_URL = 'product_url';
	const FIELD_OWNER = 'product_owner';

	/**
	 * Default Product Form URL
	 */
	const DEFAULT_FORM_URL = '/wordpress/products/add-edit-product/';

	/**
	 * AJAX Action Names
	 */
	const AJAX_LOAD_PRODUCTS = 'tmw_products_load';

	/**
	 * Nonce Actions
	 */
	const NONCE_PRODUCTS = 'tmw_products';

	/**
	 * Plugin Version
	 */
	const VERSION = '2.0.8';

	/**
	 * Get the product form URL (filterable)
	 *
	 * @return string The product form URL
	 */
	public static function get_form_url() {
		return apply_filters( 'tmw_product_form_url', self::DEFAULT_FORM_URL );
	}

	/**
	 * Get all searchable field names
	 *
	 * @return array Array of field names that should be searchable
	 */
	public static function get_searchable_fields() {
		return array(
			self::FIELD_SKU,
			self::FIELD_VENDOR,
			self::FIELD_VENDOR_SKU,
			self::FIELD_TYPE,
			self::FIELD_CONFIG,
			self::FIELD_DETAIL,
			self::FIELD_KEYWORDS,
			'keywords', // Regular ACF keywords field (in addition to keywords_raw)
			self::FIELD_ALT_VENDOR,
			self::FIELD_ALT_VENDOR_SKU,
			self::FIELD_LAUNCH,
			self::FIELD_URL,
			self::FIELD_OWNER,
		);
	}

	/**
	 * Get field labels for display
	 *
	 * @return array Associative array of field_name => label
	 */
	public static function get_field_labels() {
		return array(
			self::FIELD_SKU          => 'Internal SKU',
			self::FIELD_VENDOR       => 'Vendor Name',
			self::FIELD_VENDOR_SKU   => 'Vendor SKU',
			self::FIELD_TYPE         => 'Type',
			self::FIELD_CONFIG       => 'Configuration',
			self::FIELD_DETAIL       => 'Detail',
			self::FIELD_KEYWORDS     => 'Keywords (raw text)',
			self::FIELD_ALT_VENDOR   => 'Alternate Vendor Name',
			self::FIELD_ALT_VENDOR_SKU => 'Alternate Vendor SKU',
			self::FIELD_LAUNCH       => 'Launch Date',
			self::FIELD_URL          => 'Product URL',
			self::FIELD_OWNER        => 'Product Owner',
		);
	}
}
