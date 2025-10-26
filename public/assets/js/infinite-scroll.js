/**
 * Live Search and Infinite Scroll (jQuery)
 *
 * Simple implementation: fetch URL with search params and swap table rows.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		const $tbody = $('#tmw-results');
		const $searchInput = $('input[name="tmw_search"]');
		const $fieldSelect = $('select[name="tmw_field"]');
		const $sentinel = $('#tmw-sentinel');
		const $loader = $('#tmw-loader');
		const $counter = $('#tmw-counter');
		const $count = $('#tmw-count');

		if (!$tbody.length || !$searchInput.length) return;

		// Auto-focus search input on page load
		$searchInput.focus();

		let searchTimeout = null;
		let currentPage = 1;
		let loading = false;
		let hasMore = true;
		const baseUrl = window.location.pathname;

		/**
		 * Update product counter
		 */
		function updateCounter() {
			const currentCount = $tbody.find('tr').length;
			$count.text(currentCount);
		}

		/**
		 * Fetch and replace table rows
		 */
		function fetchProducts(page, replace) {
			if (loading) return;

			loading = true;
			$loader.show();

			const searchValue = $searchInput.val().trim();
			const fieldValue = $fieldSelect.val() || 'all';
			let url = baseUrl + '?paged=' + page;

			if (searchValue) {
				url += '&tmw_search=' + encodeURIComponent(searchValue);
				url += '&tmw_field=' + encodeURIComponent(fieldValue);
			}

			$.get(url, function(html) {
				const $html = $(html);
				const $rows = $html.find('#tmw-results tr');
				const $newCounter = $html.find('#tmw-counter');

				if (replace) {
					$tbody.html($rows);
					currentPage = 1;
					hasMore = $rows.length > 0;

					// Update counter with new total
					if ($newCounter.length) {
						$counter.html($newCounter.html());
						$counter.attr('data-total', $newCounter.attr('data-total'));
					}
				} else {
					$tbody.append($rows);
					hasMore = $rows.length > 0;
					updateCounter();
				}
			}).always(function() {
				loading = false;
				$loader.hide();
			});
		}

		/**
		 * Live search - debounced
		 */
		$searchInput.on('input', function() {
			clearTimeout(searchTimeout);
			const searchValue = $(this).val().trim();

			// If search is cleared, navigate to base URL
			if (searchValue === '') {
				window.location.href = baseUrl;
				return;
			}

			searchTimeout = setTimeout(function() {
				currentPage = 1;
				fetchProducts(1, true);
			}, 300);
		});

		/**
		 * Field select change - trigger new search
		 */
		$fieldSelect.on('change', function() {
			const searchValue = $searchInput.val().trim();
			if (searchValue) {
				currentPage = 1;
				fetchProducts(1, true);
			}
		});

		/**
		 * Infinite scroll
		 */
		if ($sentinel.length && 'IntersectionObserver' in window) {
			const observer = new IntersectionObserver(function(entries) {
				if (entries[0].isIntersecting && hasMore && !loading) {
					currentPage++;
					fetchProducts(currentPage, false);
				}
			}, {
				rootMargin: '400px'
			});
			observer.observe($sentinel[0]);
		}
	});

})(jQuery);
