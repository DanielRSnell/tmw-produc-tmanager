<?php

/*
===============================================================================
 Texas Metal Works — TMW Products Manager 
-------------------------------------------------------------------------------

 Quick map of major pieces you’ll find below:
 - CPT/Tax usage: This plugin assumes a CPT `product` and taxonomy `product_category`
   registered by CPT UI. ACF Pro provides custom fields for product details.
 - Shortcode [tmw_products]: Renders the front‑end product list UI (search input,
   results table, and the infinite scroll JS). It triggers an AJAX loader to fetch
   more rows.
 - AJAX action `tmw_products_load`: Returns rows of `product` posts as HTML <tr>’s.
   This powers both the first filtered page (after you hit Search) and subsequent
   pages as the infinite scroll requests more.
 - Shortcode [tmw_product_form]: Renders an ACF front‑end form for create/edit
   operations (post type: product). This uses `acf_form()` and respects user caps.
 - Single product view: Outputs a details/spec table for one product using values
   from ACF fields and taxonomy terms.

 Notes on search (historical context):
 - In this version, search terms are read on the client and sent to the AJAX
   endpoint via `q` in FormData. The server uses that to filter products.
 - Your infinite scroll logic appends additional rows when near the bottom.

 IMPORTANT: Comments have been added inline using PHP block comments (/* ... */)
 and JS comments (// or /* ... */) in places that are safe and do not alter code.
===============================================================================
*/

/*
Plugin Name: TMW Products Manager
Description: Products admin enhancements + front-end searchable list with infinite scroll + ACF form for add/edit + custom single Product view.
Version: 1.4.13
Author: Texas Metal Works
License: GPL2+
*/

if (!defined('ABSPATH')) exit;

/* --------------------------------------------------------------------------
 * CONFIG
 * -------------------------------------------------------------------------- */
// CPT + Taxonomy slugs
define('TMW_PROD_POST_TYPE', 'product');
define('TMW_PROD_CAT_TAX',  'product_category');

// ACF meta keys (change if your field names differ)
define('TMW_F_SKU',            'internal_sku');
define('TMW_F_VENDOR',         'vendor_name');
define('TMW_F_VENDOR_SKU',     'vendor_sku');
define('TMW_F_TYPE',           'type');
define('TMW_F_CONFIG',         'configuration');
// additional fields for single view / search
define('TMW_F_DETAIL',         'detail');
define('TMW_F_KEYWORDS',       'keywords_raw'); // we will search BOTH 'keywords' and this
define('TMW_F_ALT_VENDOR',     'alternate_vendor_name');
define('TMW_F_ALT_VENDOR_SKU', 'alternate_vendor_sku');
define('TMW_F_LAUNCH',         'launch_date');
define('TMW_F_URL',            'product_url');
define('TMW_F_OWNER',          'product_owner');

// Front-end ACF form page URL (where you place [tmw_product_form])
// You can also filter this via: apply_filters('tmw_product_form_url', TMW_PRODUCT_FORM_URL)
if (!defined('TMW_PRODUCT_FORM_URL')) {
	define('TMW_PRODUCT_FORM_URL', '/wordpress/products/add-edit-product/');
}

/* --------------------------------------------------------------------------
 * SECTION 1: ADMIN LIST ENHANCEMENTS
 * -------------------------------------------------------------------------- */
add_filter('manage_edit-' . TMW_PROD_POST_TYPE . '_columns', function($cols){
	return [
		'cb'                                   => '<input type="checkbox" />',
		'title'                                => __('Title'),
		'tmw_sku'                              => __('SKU','tmw'),
		'taxonomy-' . TMW_PROD_CAT_TAX         => __('Category','tmw'),
		'tmw_vendor'                           => __('Vendor','tmw'),
		'tmw_vendor_sku'                       => __('Vendor SKU','tmw'),
		'tmw_type'                             => __('Type','tmw'),
		'tmw_config'                           => __('Configuration','tmw'),
	];
}, 99);

add_action('manage_' . TMW_PROD_POST_TYPE . '_posts_custom_column', function($col, $post_id){
	if ($col === 'tmw_sku')        echo esc_html(get_post_meta($post_id, TMW_F_SKU, true));
	if ($col === 'tmw_vendor')     echo esc_html(get_post_meta($post_id, TMW_F_VENDOR, true));
	if ($col === 'tmw_vendor_sku') echo esc_html(get_post_meta($post_id, TMW_F_VENDOR_SKU, true));
	if ($col === 'tmw_type')       echo esc_html(get_post_meta($post_id, TMW_F_TYPE, true));
	if ($col === 'tmw_config')     echo esc_html(get_post_meta($post_id, TMW_F_CONFIG, true));
}, 10, 2);

add_filter('manage_edit-' . TMW_PROD_POST_TYPE . '_sortable_columns', function($cols){
	$cols['tmw_sku']    = 'tmw_sku';
	$cols['tmw_type']   = 'tmw_type';
	$cols['tmw_vendor'] = 'tmw_vendor';
	return $cols;
});

add_action('pre_get_posts', function($q){
	if (!is_admin() || !$q->is_main_query()) return;
	if ($q->get('post_type') !== TMW_PROD_POST_TYPE) return;

	$orderby = $q->get('orderby');
	if ($orderby === 'tmw_sku') {
		$q->set('meta_key', TMW_F_SKU);
		$q->set('orderby', 'meta_value');
	}
	if ($orderby === 'tmw_type') {
		$q->set('meta_key', TMW_F_TYPE);
		$q->set('orderby', 'meta_value');
	}
	if ($orderby === 'tmw_vendor') {
		$q->set('meta_key', TMW_F_VENDOR);
		$q->set('orderby', 'meta_value');
	}

	// Extend admin search (keep title search too)
	if ($s = $q->get('s')){
		$q->set('meta_query', [
			'relation' => 'OR',
			[ 'key'=>TMW_F_SKU,            'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_VENDOR,         'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_VENDOR_SKU,     'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_TYPE,           'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_CONFIG,         'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_DETAIL,         'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_KEYWORDS,       'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>'keywords',           'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_ALT_VENDOR,     'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_ALT_VENDOR_SKU, 'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_LAUNCH,         'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_URL,            'value'=>$s, 'compare'=>'LIKE' ],
			[ 'key'=>TMW_F_OWNER,          'value'=>$s, 'compare'=>'LIKE' ],
		]);
	}
});

// Row action: Copy SKU (snackbar)
add_filter('post_row_actions', function($actions, $post){
	if ($post->post_type !== TMW_PROD_POST_TYPE) return $actions;
	$sku = get_post_meta($post->ID, TMW_F_SKU, true);
	if ($sku){
		$actions['tmw_copy_sku'] = '<a href="#" class="tmw-copy-sku" data-sku="' . esc_attr($sku) . '">' . __('Copy SKU','tmw') . '</a>';
	}
	return $actions;
}, 10, 2);

add_action('admin_footer-edit.php', function(){
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== TMW_PROD_POST_TYPE) return; ?>
<script>
document.addEventListener('click', function(e){
	var a = e.target.closest('.tmw-copy-sku');
	if(!a) return;
	e.preventDefault();
	navigator.clipboard.writeText(a.dataset.sku).then(function(){
		if (window.wp && wp.data) wp.data.dispatch('core/notices').createNotice('success','SKU copied to clipboard',{type:'snackbar'});
	});
});
</script>
<?php });

add_filter('default_hidden_columns', function($hidden, $screen){
	if ($screen->id === 'edit-' . TMW_PROD_POST_TYPE) return [];
	return $hidden;
}, 10, 2);

/* --------------------------------------------------------------------------
 * SECTION 2: FRONT-END LIST [tmw_products] with Infinite Scroll
 * -------------------------------------------------------------------------- */
if (!function_exists('tmw_products_ajax_load')) {
	/* AJAX handler: reads paging/search from POST and echoes <tr> rows for the table */
function tmw_products_ajax_load(){
		check_ajax_referer('tmw_products');

		$paged     = max(1, intval($_POST['page'] ?? 1));
		$per_page  = min(500, max(1, intval($_POST['per_page'] ?? 50)));
		$qtxt      = sanitize_text_field($_POST['q'] ?? '');
		$catid     = intval($_POST['cat'] ?? 0);

		$args = [
			'post_type'      => TMW_PROD_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			's'              => $qtxt,
		];
		if ($qtxt){
			$args['meta_query'] = [
				'relation' => 'OR',
				[ 'key'=>TMW_F_SKU,            'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_VENDOR,         'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_VENDOR_SKU,     'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_TYPE,           'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_CONFIG,         'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_DETAIL,         'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_KEYWORDS,       'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>'keywords',           'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_ALT_VENDOR,     'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_ALT_VENDOR_SKU, 'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_LAUNCH,         'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_URL,            'value'=>$qtxt, 'compare'=>'LIKE' ],
				[ 'key'=>TMW_F_OWNER,          'value'=>$qtxt, 'compare'=>'LIKE' ],
			];
		}
		if ($catid){
			$args['tax_query'] = [[
				'taxonomy' => TMW_PROD_CAT_TAX,
				'field'    => 'term_id',
				'terms'    => $catid,
			]];
		}

		$q = new WP_Query($args);
		$rows = '';
		if ($q->have_posts()){
			while($q->have_posts()){ $q->the_post();
				$id    = get_the_ID();
				$sku   = get_post_meta($id, TMW_F_SKU, true);
				$vend  = get_post_meta($id, TMW_F_VENDOR, true);
				$type  = get_post_meta($id, TMW_F_TYPE, true);
				$conf  = get_post_meta($id, TMW_F_CONFIG, true);
				$terms = get_the_terms($id, TMW_PROD_CAT_TAX);
				$catn  = $terms && !is_wp_error($terms) ? implode(', ', wp_list_pluck($terms,'name')) : '';
				$rows .= '<tr class="tmw-row"><td class="tmw-col-title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></td>'
					. '<td class="tmw-col-sku">' . esc_html($sku) . '</td>'
					. '<td>' . esc_html($catn) . '</td>'
					. '<td>' . esc_html($vend) . '</td>'
					. '<td>' . esc_html($type) . '</td>'
					. '<td>' . esc_html($conf) . '</td></tr>';
			}
			wp_reset_postdata();
		}

		wp_send_json_success([
			'rows'     => $rows,
			'has_more' => ($q->max_num_pages > $paged),
		]);
	}
}
/* AJAX (logged-in): loads product rows for infinite scroll / filtered results */
add_action('wp_ajax_tmw_products_load', 'tmw_products_ajax_load');
/* AJAX (public): loads product rows for infinite scroll / filtered results */
add_action('wp_ajax_nopriv_tmw_products_load', 'tmw_products_ajax_load');

/* Shortcode [tmw_products]: front-end list UI (search + table + infinite scroll) */
add_shortcode('tmw_products', function($atts){
	$atts = shortcode_atts([
		'per_page' => 50,        // initial chunk size
		'add_url'  => '',        // optional: show Add Product button linking here
	], $atts, 'tmw_products');

	$per_page = min(500, max(1, intval($_GET['per'] ?? $atts['per_page'])));
	$qtxt     = sanitize_text_field($_GET['q'] ?? '');
	$catid    = intval($_GET['cat'] ?? 0);

	$cats  = get_terms(['taxonomy'=>TMW_PROD_CAT_TAX, 'hide_empty'=>false]);
	$nonce = wp_create_nonce('tmw_products');
	$add_url = esc_url($atts['add_url']);

	ob_start(); ?>
	<style>
	.tmw-wrap{background:#fff;border:1px solid #e6e6e6;border-radius:8px;padding:32px 16px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
	.tmw-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
	.tmw-filter input{padding:6px; min-width:340px}
	.tmw-filter select{padding:6px; min-width:200px}
	.tmw-table{width:100%;border-collapse:collapse}
	.tmw-table th,.tmw-table td{padding:8px 10px;border-bottom:1px solid #f0f0f0;text-align:center}
	.tmw-table .tmw-col-title{text-align:left}
	.tmw-table .tmw-col-sku{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace}
	.tmw-table tbody tr:nth-child(odd){background:#fafafa}
	.tmw-table tbody tr:hover{background:#f5faff}
	.tmw-table thead th{position:sticky;top:0;background:#f8f8f8;z-index:2;border-bottom:2px solid #e5e5e5}
	.tmw-sentinel{height:1px}
	.tmw-loading{opacity:.6;transition:opacity .2s}
	.tmw-add-btn{padding:8px 12px;background:#1e73be;color:#fff;border-radius:4px;display:inline-block;text-decoration:none}
	</style>

	<div class="tmw-wrap" id="tmw-products">
		<?php if ($add_url && is_user_logged_in() && current_user_can('edit_posts')): ?>
			<div style="margin-bottom:10px;text-align:right"><a class="tmw-add-btn" href="<?php echo $add_url; ?>">+ Add Product</a></div>
		<?php endif; ?>

		<form method="get" class="tmw-filter" id="tmw-filter-form">
			<input type="text" name="q" value="<?php echo esc_attr($qtxt); ?>" placeholder="Search title, SKU, vendor, type, configuration, keywords">
			<button type="submit">Search</button>
		</form>

		<table class="tmw-table" id="tmw-table">
			<thead><tr>
				<th class="tmw-col-title">Title</th>
				<th>SKU</th>
				<th>Category</th>
				<th>Vendor</th>
				<th>Type</th>
				<th>Configuration</th>
			</tr></thead>
			/* Results table body: rows are inserted here (initial & as you scroll) */
<tbody id="tmw-tbody"></tbody>
		</table>
		<div class="tmw-sentinel" id="tmw-sentinel"></div>
	</div>

	<script>
	(function(){
		/* UI state: current page, query, loading flags used by infinite scroll */
const state = {
			page: 1,
			loading: false,
			hasMore: true,
			perPage: <?php echo (int)$per_page; ?>,
			q: <?php echo json_encode($qtxt); ?>,
			cat: <?php echo (int)$catid; ?>,
			nonce: <?php echo json_encode($nonce); ?>
		};
		
    // tmwpm_submit_singlebox
    (function(){
      document.addEventListener('DOMContentLoaded', function(){
        var form = document.getElementById('tmw-filter-form');
        if(!form) return;
        form.addEventListener('submit', function(e){
          e.preventDefault();
          var qEl = form.querySelector('input[name="q"]');
          state.q = qEl ? (qEl.value||'').trim() : '';
          state.page = 1; state.hasMore = true;
          try{ document.getElementById('tmw-tbody').innerHTML=''; }catch(_){}
          if (typeof loadNext === 'function') loadNext();
        });
      });
    })();
    
		const tbody = document.getElementById('tmw-tbody');
		const sentinel = document.getElementById('tmw-sentinel');
		const wrap = document.getElementById('tmw-products');

		async /* Infinite scroll loader: fetches next page of rows and appends to <tbody> */
\1){
			if (state.loading || !state.hasMore) return;
			state.loading = true; wrap.classList.add('tmw-loading');
			try{
				const form = new FormData();
				form.append('action','tmw_products_load');
				form.append('_ajax_nonce', state.nonce);
				form.append('page', state.page);
				form.append('per_page', state.perPage);
				form.append('q', state.q);
				form.append('cat', 0);
				const res = await fetch(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, { method:'POST', body:form });
				const json = await res.json();
				if(json && json.success){
					if(json.data.rows){
						const tmp = document.createElement('tbody');
						tmp.innerHTML = json.data.rows;
						while(tmp.firstChild){ tbody.appendChild(tmp.firstChild); }
					}
					state.hasMore = !!json.data.has_more;
					state.page++;
				}
			} catch(e) { console.error(e); }
			state.loading = false; wrap.classList.remove('tmw-loading');
		}

		const io = new IntersectionObserver(entries => {
			entries.forEach(entry => { if(entry.isIntersecting) loadNext(); });
		},{ rootMargin: '600px 0px 600px 0px' });
		io.observe(sentinel);

		loadNext();
	})();
	</script>
	<?php
	return ob_get_clean();
});

/* --------------------------------------------------------------------------
 * SECTION 3: ACF FRONT-END FORM [tmw_product_form] — Add OR Edit
 * -------------------------------------------------------------------------- */
add_action('template_redirect', function(){
	if (!function_exists('acf_form_head')) return;
	if (!is_page()) return;
	$content = get_post_field('post_content', get_queried_object_id());
	if ($content && has_shortcode($content, 'tmw_product_form')) acf_form_head();
});

/* Shortcode [tmw_product_form]: front-end ACF form for add/edit product */
add_shortcode('tmw_product_form', function(){
	if (!function_exists('acf_form')) return '<p>ACF Pro is not active.</p>';
	if (!is_user_logged_in()) return '<p>Please log in to submit a product.</p>';

	$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
	$post_id = 'new_post';
	if ($edit_id && get_post_type($edit_id) === TMW_PROD_POST_TYPE && current_user_can('edit_post', $edit_id)) {
		$post_id = $edit_id; // edit mode
	}

	$css = <<<CSS
.tmw-acf-form .acf-form{background:#fff;border:1px solid #e6e6e6;border-radius:10px;padding:16px}
.tmw-acf-form .acf-fields{display:flex;flex-wrap:wrap;gap:12px}
/* default full-width */
.tmw-acf-form .acf-field{flex:1 1 100%;margin:0}
/* Row 1: SKU + Launch + Owner */
.tmw-acf-form .acf-field[data-name="internal_sku"],
.tmw-acf-form .acf-field[data-name="launch_date"],
.tmw-acf-form .acf-field[data-name="product_owner"]{order:1;flex:1 1 calc(33.33% - 12px)}
/* Row 2: Category + Type + Configuration (three across) */
.tmw-acf-form .acf-field[data-name="product_category"],
.tmw-acf-form .acf-field[data-name="type"],
.tmw-acf-form .acf-field[data-name="configuration"]{order:2;flex:1 1 calc(33.33% - 12px)}
/* Keep Configuration compact if it's a textarea */
.tmw-acf-form .acf-field[data-name="configuration"] textarea{min-height:34px;height:34px;resize:vertical}
/* Row 3: Detail full-width + short height */
.tmw-acf-form .acf-field[data-name="detail"]{order:3;flex-basis:100%}
.tmw-acf-form .acf-field[data-name="detail"] textarea{min-height:48px}
/* Row 4: Vendor + Vendor SKU split */
.tmw-acf-form .acf-field[data-name="vendor_name"],
.tmw-acf-form .acf-field[data-name="vendor_sku"]{order:4;flex:1 1 calc(50% - 12px)}
/* Row 5: Alt Vendor + Alt Vendor SKU split */
.tmw-acf-form .acf-field[data-name="alternate_vendor_name"],
.tmw-acf-form .acf-field[data-name="alternate_vendor_sku"]{order:5;flex:1 1 calc(50% - 12px)}
/* Row 6: Keywords + Product URL split */
.tmw-acf-form .acf-field[data-name="keywords_raw"],
.tmw-acf-form .acf-field[data-name="product_url"]{order:6;flex:1 1 calc(50% - 12px)}
/* inputs/labels tighter */
.tmw-acf-form .acf-label label{font-size:12px;color:#555;margin-bottom:4px}
.tmw-acf-form .acf-input input,
.tmw-acf-form .acf-input select{height:34px;padding:6px}
.tmw-acf-form .acf-input textarea{min-height:80px}
CSS;

	ob_start();
	echo '<style>' . $css . '</style>';
	echo '<div class="tmw-acf-form">';
	echo ($post_id === 'new_post') ? '<h3>Add Product</h3>' : '<h3>Edit Product: '.esc_html(get_the_title($post_id)).'</h3>';

	acf_form([
		'post_id'      => $post_id,
		'new_post'     => ['post_type'=>TMW_PROD_POST_TYPE, 'post_status'=>'publish'],
		'field_groups' => [],
		'submit_value' => ($post_id === 'new_post') ? 'Save Product' : 'Update Product',
		'uploader'     => 'wp',
	]);
	echo '</div>';
	return ob_get_clean();
});

/* --------------------------------------------------------------------------
 * SECTION 4: SINGLE PRODUCT VIEW (front-end)
 * -------------------------------------------------------------------------- */
add_filter('the_content', function($content){
	if (!is_singular(TMW_PROD_POST_TYPE) || !in_the_loop() || !is_main_query()) return $content;

	$id    = get_the_ID();
	$title = get_the_title();
	$sku   = get_post_meta($id, TMW_F_SKU, true);
	$vend  = get_post_meta($id, TMW_F_VENDOR, true);
	$vend_sku = get_post_meta($id, TMW_F_VENDOR_SKU, true);
	$type  = get_post_meta($id, TMW_F_TYPE, true);
	$conf  = get_post_meta($id, TMW_F_CONFIG, true);
	$detail= get_post_meta($id, TMW_F_DETAIL, true);
	$kws   = get_post_meta($id, TMW_F_KEYWORDS, true);
	$alt_v = get_post_meta($id, TMW_F_ALT_VENDOR, true);
	$alt_vs= get_post_meta($id, TMW_F_ALT_VENDOR_SKU, true);
	$launch= get_post_meta($id, TMW_F_LAUNCH, true);
	$url   = get_post_meta($id, TMW_F_URL, true);
	$owner = get_post_meta($id, TMW_F_OWNER, true);
	if ($owner && is_numeric($owner)) { $u = get_user_by('id', (int)$owner); if ($u) $owner = $u->display_name; }
	$terms = get_the_terms($id, TMW_PROD_CAT_TAX);
	$catn  = $terms && !is_wp_error($terms) ? implode(', ', wp_list_pluck($terms,'name')) : '';

	$form_url = apply_filters('tmw_product_form_url', TMW_PRODUCT_FORM_URL);
	$can_edit = is_user_logged_in() && current_user_can('edit_post', $id);

	$css = <<<CSS
.tmw-single{background:#fff;border:1px solid #e6e6e6;border-radius:10px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.tmw-single h1{margin-top:0}
.tmw-specs{width:100%;border-collapse:collapse;margin-top:10px}
.tmw-specs th,.tmw-specs td{padding:10px;border-bottom:1px solid #f0f0f0;text-align:left;vertical-align:top}
.tmw-specs th{width:220px;color:#555;background:#fafafa}
.tmw-actions{margin-top:16px;display:flex;gap:10px}
.tmw-btn{padding:8px 12px;border-radius:6px;text-decoration:none;display:inline-block}
.tmw-btn-primary{background:#1e73be;color:#fff}
CSS;

	ob_start();
	echo '<style>' . $css . '</style>';
	?>
	<div class="tmw-single">
		<h1><?php echo esc_html($title); ?></h1>
		<table class="tmw-specs">
			<tr><th>SKU</th><td><?php echo esc_html($sku ?: '—'); ?></td></tr>
			<tr><th>Category</th><td><?php echo esc_html($catn ?: '—'); ?></td></tr>
			<tr><th>Vendor</th><td><?php echo esc_html($vend ?: '—'); ?></td></tr>
			<tr><th>Vendor SKU</th><td><?php echo esc_html($vend_sku ?: '—'); ?></td></tr>
			<tr><th>Alternate Vendor Name</th><td><?php echo esc_html($alt_v ?: '—'); ?></td></tr>
			<tr><th>Alternate Vendor SKU</th><td><?php echo esc_html($alt_vs ?: '—'); ?></td></tr>
			<tr><th>Type</th><td><?php echo esc_html($type ?: '—'); ?></td></tr>
			<tr><th>Configuration</th><td><?php echo esc_html($conf ?: '—'); ?></td></tr>
			<tr><th>Detail</th><td><?php echo nl2br(esc_html($detail ?: '—')); ?></td></tr>
			<tr><th>Keywords</th><td><?php echo esc_html($kws ?: '—'); ?></td></tr>
			<tr><th>Launch Date</th><td><?php echo esc_html($launch ?: '—'); ?></td></tr>
			<tr><th>Product URL</th><td><?php echo $url ? '<a href="'.esc_url($url).'" target="_blank" rel="noopener">'.esc_html($url).'</a>' : '—'; ?></td></tr>
			<tr><th>Product Owner</th><td><?php echo esc_html($owner ?: '—'); ?></td></tr>
		</table>
		<?php if ($can_edit && $form_url): ?>
			<div class="tmw-actions">
				<a class="tmw-btn tmw-btn-primary" href="<?php echo esc_url( trailingslashit($form_url) . '?edit=' . $id ); ?>">Edit Product</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
});
