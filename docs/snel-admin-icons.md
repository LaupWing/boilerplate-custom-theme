# Snel Admin Menu Icons

## How It Works

WordPress applies a CSS filter to admin menu icons, making them monochrome. The Snel Stack theme overrides this with custom CSS + JavaScript that replaces the default `<img>` tag with a branded gradient circle + white SVG icon.

**File:** `inc/admin/snelstack/index.php`

## Adding a New Icon

### Step 1: Register your menu page with any icon

In your plugin's `add_menu_page()`, use a base64 SVG as a fallback. It won't be visible — the JS replaces it — but WordPress needs something here.

```php
add_menu_page(
    'My Plugin',
    'My Plugin',
    'manage_options',
    'snel-myplugin',
    'render_callback',
    'dashicons-admin-generic', // fallback, gets replaced
    28
);
```

### Step 2: Add your SVG to the JS icon map

In `inc/admin/snelstack/index.php`, find the `snelIcons` object and add your entry:

```js
var snelIcons = {
    "snel-seo": '...existing...',
    "snel-translations": '...existing...',
    "snel-newsletter": '...existing...',
    "snel-myplugin": '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">YOUR PATHS HERE</svg>',
    "snelstack": '...existing...'
};
```

**Key:** must match the slug from `add_menu_page()` (the 4th parameter).

### Step 3: Add your slug to the CSS selectors (3 places)

Find each CSS selector block and add your line:

```css
/* 1. Flex + overflow */
#adminmenu .toplevel_page_snel-myplugin .wp-menu-image {
    display: flex !important;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-image: none !important;
}

/* 2. Position relative */
#adminmenu .toplevel_page_snel-myplugin {
    position: relative;
    z-index: 1;
}

/* 3. Hide br */
#adminmenu .toplevel_page_snel-myplugin .wp-menu-image br {
    display: none;
}
```

### Step 4: Add your slug to the JS querySelector

```js
document.querySelectorAll(
    "#adminmenu .toplevel_page_snel-seo .wp-menu-image," +
    "#adminmenu .toplevel_page_snel-myplugin .wp-menu-image," +  // add this
    "#adminmenu .toplevel_page_snelstack .wp-menu-image"
)
```

## SVG Guidelines

- **ViewBox:** `0 0 24 24` (Lucide icon standard)
- **Stroke:** `#fff`, width `2` (or `1.5` for filled icons)
- **Fill:** `none` for stroke icons, `#fff` for solid icons
- **Size:** The CSS scales it to 14x14px inside a 22x22px gradient circle
- **Source:** Use icons from [Lucide](https://lucide.dev) — copy the SVG markup directly

## Active State

When a menu item is active (current page), the JS automatically:
- Adds `is-active` class (removes static gradient background)
- Injects a `snel-gradient-ring` element (animated rainbow conic-gradient that spins)

No extra work needed — this happens for all registered Snel icons.

## Checklist

- [ ] Menu page registered with slug starting with `snel-`
- [ ] SVG added to `snelIcons` object (key = slug)
- [ ] CSS selector added in 3 places (`.wp-menu-image`, `<li>`, `br`)
- [ ] JS `querySelectorAll` updated with new selector
