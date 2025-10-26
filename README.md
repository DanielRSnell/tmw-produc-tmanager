# TMW Product Manager

A comprehensive WordPress plugin for managing product catalogs with live search, infinite scroll, and ACF-powered forms.

## Features

- **Product Archive** - Full-featured product listing with live search and infinite scroll
- **Single Product View** - Detailed product specifications with all custom fields
- **ACF Front-End Form** - Add/edit products from the front-end
- **Advanced Search** - Search all fields or specific fields (SKU, Vendor, Type, etc.)
- **Infinite Scroll** - Smooth loading of products as you scroll
- **Staggered Animations** - Subtle, high-quality fade-in animations
- **Responsive Design** - Horizontal scrolling tables with sticky headers
- **Product Counter** - Shows "X of Y products" with live updates
- **View Transitions** - Smooth navigation between pages
- **Admin Enhancements** - Custom columns, sortable fields, and quick actions

## Requirements

- WordPress 5.0+
- ACF Pro (Advanced Custom Fields Pro)
- PHP 7.4+

## Installation

### Before Installing

**IMPORTANT:** Follow these steps in order to avoid conflicts:

1. **Deactivate CPT UI Plugin**
   - Go to `Plugins > Installed Plugins`
   - Deactivate "Custom Post Type UI" if currently active
   - This plugin now uses ACF to register the Product post type

2. **Import ACF Configuration**
   - Go to `ACF > Tools > Import`
   - Select the `import-this.json` file from this plugin directory
   - Click "Import JSON"
   - This imports the Product post type, Product Category taxonomy, and all custom fields

3. **Clean Up Old Pages/Shortcodes**
   - Delete any "Products" page previously created for shortcodes
   - Remove any `[tmw_products]` or `[tmw_product_form]` shortcodes
   - The plugin now uses native WordPress archive/single templates

### Install the Plugin

1. Upload the `tmw-product-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to `/products/` to see the product archive

## What Gets Created

After importing `import-this.json`, ACF creates:

- **Post Type:** `product`
- **Taxonomy:** `product_category`
- **Archive Page:** Automatically available at `/products/`
- **Custom Fields:**
  - Internal SKU
  - Vendor Name
  - Vendor SKU
  - Type
  - Configuration
  - Detail
  - Keywords
  - Alternate Vendor Name
  - Alternate Vendor SKU
  - Launch Date
  - Product URL
  - Product Owner

## Usage

### Viewing Products

Visit `/products/` to see the product archive with:
- Live search (auto-focused, debounced)
- Field-specific search (search all fields or specific ones)
- Infinite scroll
- Product counter
- Staggered animations

### Adding/Editing Products

**From Admin:**
- Go to `Products > Add New` in WordPress admin
- Fill in all custom fields
- Publish

**From Front-End:**
- Click "Edit Product" button on any product page (if logged in with edit permissions)
- Or visit a product and click "Edit Product" in the top navigation

### Searching Products

Use the search box on the archive page:
- **All Fields** - Searches across all product data
- **Product Name** - Searches titles only
- **SKU** - Searches internal SKU only
- **Vendor** - Searches vendor name only
- **Vendor SKU** - Searches vendor SKU only
- **Type, Configuration, Detail, etc.** - Field-specific searches

### Back Navigation

Single product pages have a "← Back to Products" button that:
- Returns to the product archive
- Scrolls to the specific product row with an anchor link
- Uses view transitions for smooth navigation

## Admin Features

### Custom Columns

The admin product list shows:
- Title
- SKU
- Category
- Vendor
- Vendor SKU
- Type
- Configuration

### Sortable Columns

Click column headers to sort by:
- SKU
- Type
- Vendor

### Quick Actions

- **Copy SKU** - Click "Copy SKU" in row actions to copy to clipboard (shows snackbar notification)

### Enhanced Search

Admin search includes all custom fields:
- SKU, Vendor, Type, Configuration
- Detail, Keywords, URLs
- Alternate vendor information

## File Structure

```
tmw-product-manager/
├── admin/
│   └── class-admin-columns.php      # Admin list customization
├── ajax/
│   └── class-product-ajax.php       # AJAX handlers for infinite scroll
├── includes/
│   ├── class-config.php             # Configuration constants
│   ├── class-plugin.php             # Main plugin class
│   └── class-product-fields.php     # Field handling utilities
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── product-list.css     # Archive styles
│   │   │   ├── single-product.css   # Single view styles
│   │   │   └── product-form.css     # Form styles
│   │   └── js/
│   │       └── infinite-scroll.js   # Live search & infinite scroll
│   └── class-product-form.php       # Front-end form (legacy shortcode)
├── templates/
│   ├── archive-product.php          # Product archive template
│   └── single-product.php           # Single product template
├── import-this.json                 # ACF configuration to import
├── tmw-product-manager.php          # Main plugin file
└── README.md                        # This file
```

## Development

### Adding Test Products

Use WP-CLI to add test products:

```bash
wp post create --post_type=product --post_title="Test Product" --post_status=publish \
  --meta_input='{"internal_sku":"TMW-001","vendor_name":"Test Vendor"}'
```

### Hooks & Filters

**Filter product form URL:**
```php
add_filter('tmw_product_form_url', function($url) {
    return '/custom-form-url/';
});
```

## Styling

The plugin uses a clean, modern design with:
- Light gray background (`#f8fafc`)
- White content cards with subtle shadows
- 12px border radius
- Smooth animations and transitions
- Responsive tables with horizontal scroll
- Sticky table headers

WordPress admin bar is hidden on all product pages for a cleaner interface.

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Changelog

### Version 2.0.0
- Refactored to modular architecture
- Moved from CPT UI to ACF for post type registration
- Added native WordPress archive/single templates
- Removed shortcode dependency
- Added field-specific search
- Added staggered row animations
- Added product counter
- Improved infinite scroll performance
- Enhanced admin features

## Credits

**Author:** Texas Metal Works
**License:** GPL2+

## Support

For issues or feature requests, contact your development team.
