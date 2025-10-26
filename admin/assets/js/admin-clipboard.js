/**
 * Admin Clipboard Handler
 *
 * Handles "Copy SKU" functionality in the admin product list table.
 *
 * @package TMW_Product_Manager
 * @since 2.0.0
 */

document.addEventListener('click', function(e) {
	var a = e.target.closest('.tmw-copy-sku');
	if (!a) return;

	e.preventDefault();

	navigator.clipboard.writeText(a.dataset.sku).then(function() {
		// Display success notification using WordPress notices API
		if (window.wp && wp.data) {
			wp.data.dispatch('core/notices').createNotice(
				'success',
				'SKU copied to clipboard',
				{ type: 'snackbar' }
			);
		}
	});
});
