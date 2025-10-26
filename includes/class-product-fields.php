<?php
/**
 * Product Fields Helper Class
 *
 * Provides utility methods for working with product fields and meta queries.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMW_Product_Fields class
 *
 * Helper class for field-related operations.
 */
class TMW_Product_Fields {

	/**
	 * Get a product field value
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field_name Field name.
	 * @param bool   $single Whether to return single value.
	 * @return mixed Field value
	 */
	public static function get_field( $post_id, $field_name, $single = true ) {
		return get_post_meta( $post_id, $field_name, $single );
	}

	/**
	 * Build meta query for search across all product fields
	 *
	 * @param string $search_query The search query string.
	 * @return array Meta query array for WP_Query
	 */
	public static function build_search_meta_query( $search_query ) {
		$fields = TMW_Config::get_searchable_fields();
		$meta_query = array( 'relation' => 'OR' );

		foreach ( $fields as $field ) {
			$meta_query[] = array(
				'key'     => $field,
				'value'   => $search_query,
				'compare' => 'LIKE',
			);
		}

		return $meta_query;
	}

	/**
	 * Get formatted field value for display
	 *
	 * Handles special cases like URLs, dates, and user objects.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field_name Field name.
	 * @return string Formatted field value
	 */
	public static function get_formatted_field( $post_id, $field_name ) {
		$value = self::get_field( $post_id, $field_name );

		// Handle empty values
		if ( empty( $value ) ) {
			return '—';
		}

		// Handle product owner (user object)
		if ( TMW_Config::FIELD_OWNER === $field_name ) {
			if ( is_array( $value ) && isset( $value['display_name'] ) ) {
				return esc_html( $value['display_name'] );
			} elseif ( is_numeric( $value ) ) {
				$user = get_userdata( $value );
				return $user ? esc_html( $user->display_name ) : '—';
			}
			return '—';
		}

		// Handle URLs
		if ( TMW_Config::FIELD_URL === $field_name && filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $value ),
				esc_html( $value )
			);
		}

		// Handle dates
		if ( TMW_Config::FIELD_LAUNCH === $field_name ) {
			$timestamp = strtotime( $value );
			if ( $timestamp ) {
				return esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
			}
		}

		// Default: escape and return
		return esc_html( $value );
	}

	/**
	 * Get product category terms
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $links Whether to return as links.
	 * @return string Comma-separated category names
	 */
	public static function get_categories( $post_id, $links = false ) {
		$terms = get_the_terms( $post_id, TMW_Config::CATEGORY_TAX );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return '—';
		}

		if ( $links ) {
			$term_links = array();
			foreach ( $terms as $term ) {
				$term_links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_term_link( $term ) ),
					esc_html( $term->name )
				);
			}
			return implode( ', ', $term_links );
		}

		$term_names = wp_list_pluck( $terms, 'name' );
		return esc_html( implode( ', ', $term_names ) );
	}

	/**
	 * Sanitize and validate field value for saving
	 *
	 * @param string $field_name Field name.
	 * @param mixed  $value Field value.
	 * @return mixed Sanitized value
	 */
	public static function sanitize_field( $field_name, $value ) {
		// Handle URLs
		if ( TMW_Config::FIELD_URL === $field_name ) {
			return esc_url_raw( $value );
		}

		// Handle dates
		if ( TMW_Config::FIELD_LAUNCH === $field_name ) {
			return sanitize_text_field( $value );
		}

		// Handle textareas
		if ( in_array( $field_name, array( TMW_Config::FIELD_CONFIG, TMW_Config::FIELD_DETAIL ), true ) ) {
			return sanitize_textarea_field( $value );
		}

		// Default: text field
		return sanitize_text_field( $value );
	}
}
