# Snel Admin Menu Icons

## Why this exists

WordPress's `wp-includes/js/svg-painter.js` recolors all admin menu SVG icons to match the admin color scheme. For SVGs with gradients/defs, it strips them and rewrites every `fill` to a single solid color — the icon flashes correct on page load, then turns white. svg-painter is a core WP feature, not a bug. We work around it.

## How it works

The theme renders a branded gradient circle + white SVG outline in place of WP's recolored icon. One PHP function holds all icon data; CSS selectors and JS replacement are generated from it dynamically.

**File:** `inc/admin/snelstack/index.php` — function `snelstack_get_admin_icons()`.

## Adding a new icon

### Option A — Edit the theme array (1 line)

Open `inc/admin/snelstack/index.php`, add an entry to `snelstack_get_admin_icons()`:

```php
return apply_filters( 'snel_admin_icons', array(
    'snel-seo'          => '<svg ...>...</svg>',
    'snel-translations' => '<svg ...>...</svg>',
    'snel-newsletter'   => '<svg ...>...</svg>',
    'snel-myplugin'     => '<svg ...>...</svg>',  // ← add this
    'snelstack'         => '<svg ...>...</svg>',
) );
```

The CSS selectors, the JS `querySelectorAll`, and the icon map all derive from the array keys. No other edits needed.

### Option B — From the plugin itself (no theme edits)

Plugins can register their own icon via the `snel_admin_icons` filter:

```php
add_filter( 'snel_admin_icons', function ( $icons ) {
    $icons['snel-myplugin'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">YOUR PATHS</svg>';
    return $icons;
} );
```

The slug **must match** the 4th argument of the plugin's `add_menu_page()` call.

## Plugin-side registration

In your plugin's `add_menu_page()`, pass any placeholder icon (it gets replaced at runtime):

```php
add_menu_page(
    'My Plugin',
    'My Plugin',
    'manage_options',
    'snel-myplugin',
    'render_callback',
    'dashicons-admin-generic', // placeholder, gets replaced by the theme
    28
);
```

## SVG guidelines

- **ViewBox:** `0 0 24 24` (Lucide icon standard)
- **Stroke:** `#fff`, width `2` (or `1.5` for filled icons)
- **Fill:** `none` for stroke icons, `#fff` for solid
- **Size:** CSS scales it to 14×14 inside a 22×22 gradient circle
- **Source:** [Lucide](https://lucide.dev) — copy the SVG markup directly

## Active state

When the menu item is the current page, the JS:
- Adds `is-active` class (removes the static gradient background)
- Injects a `snel-gradient-ring` element — animated rainbow conic-gradient that spins

Automatic for all registered Snel icons; no extra work.

## Checklist

- [ ] Plugin's `add_menu_page()` slug starts with `snel-` (or is `snelstack`)
- [ ] SVG added to `snelstack_get_admin_icons()` (theme) **or** registered via `snel_admin_icons` filter (plugin)
- [ ] Slug used as the array key matches the 4th arg of `add_menu_page()`
