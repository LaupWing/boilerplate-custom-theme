# Translation System

Custom multilingual system without WPML or Polylang. Supports translated URLs (slugified), theme strings, block content, and CPT fields.

## How it works

### Language detection
- Language is detected from the URL prefix: `/en/about/`, `/de/kontakt/`
- Default language (configured in `config/languages.php`) has no prefix: `/over-ons/`
- Detected via `bp_get_lang()` — checks the `lang` query var set by rewrite rules

### URL routing
Rewrite rules are registered on `init` in `bp_lang_rewrite_rules()`:

```
/en/              → homepage (lang=en)
/en/services/     → CPT archive (lang=en, post_type=diensten)
/en/services/x/   → CPT single (lang=en, post_type=diensten, name=x)
/en/about-us/     → page (lang=en, page_id=123)
/en/anything/     → catch-all (lang=en, pagename=anything)
```

**Important:** After changing rewrite rules or adding translated slugs, you must flush permalinks (Settings > Permalinks > Save Changes) or re-seed pages.

### Where translations are stored

| What | Where | Format |
|------|-------|--------|
| Page URL slugs | `wp_postmeta` | `_slug_{lang}` = translated slug |
| Page titles (SEO) | `wp_postmeta` | `_title_{lang}` = translated title |
| Meta descriptions | `wp_postmeta` | `_meta_desc_{lang}` = translated desc |
| CPT archive slugs | `inc/translations/config/slugs-cpt.php` | PHP array config |
| Theme strings | `wp_options` (`bp_theme_translations`) | DB, editable in admin |
| Theme string defaults | `inc/translations.php` | PHP array fallback |
| Block content | Gutenberg block attributes | `{nl: "...", en: "...", de: "..."}` objects |
| CPT field translations | `wp_postmeta` | Stored as `{nl: "...", en: "..."}` arrays |

### The page_id rewrite trick

**Problem:** When using translated slugs (e.g., Dutch page slug is `over-ons`, but the real WordPress slug is `about`), the catch-all rewrite rule sets `pagename=over-ons`. WordPress's `parse_request()` then tries to find a page with slug `over-ons`. It doesn't exist, so WP silently converts it to `attachment=over-ons` → 404.

**Solution:** For every page that has a `_slug_{lang}` meta, we generate an explicit rewrite rule using `page_id` instead of `pagename`:

```
^en/about-us/?$ → index.php?lang=en&page_id=123
```

This bypasses WordPress's slug lookup entirely. The catch-all rule (`pagename=$matches[1]`) remains as fallback for pages without translated slugs.

**When this matters:** Only when you use translated slugs (different URL per language). If all languages use the same slug, the catch-all works fine.

### Adding a new translated page

1. Add the page to `inc/seeders/seed-pages.php` with `slugs` array:
   ```php
   [
       'title' => 'About',
       'slug'  => 'over-ons',           // Default language (Dutch) slug
       'slugs' => [
           'en' => 'about-us',          // Stored as _slug_en post meta
           'de' => 'ueber-uns',         // Stored as _slug_de post meta
       ],
   ]
   ```
2. Run the seeder (Tools > Seed Pages) — it saves `_slug_{lang}` meta
3. Flush permalinks (Settings > Permalinks > Save) — registers the `page_id` rewrite rules

Or manually: edit the page in wp-admin, fill in the translated slugs in the "Translations" meta box, then flush permalinks.

### Adding a translated CPT archive

1. Edit `inc/translations/config/slugs-cpt.php`:
   ```php
   return [
       'diensten' => ['en' => 'services', 'de' => 'dienstleistungen'],
   ];
   ```
2. Flush permalinks

### URL helper functions

| Function | Purpose | Example |
|----------|---------|---------|
| `bp_url($url)` | Add language prefix to any URL (handles absolute URLs) | `bp_url('/contact/')` → `/en/contact/` |
| `bp_page_url($slug)` | Translated page URL from default slug | `bp_page_url('over-ons')` → `/en/about-us/` |
| `bp_cpt_url($slug)` | Translated CPT archive URL | `bp_cpt_url('diensten')` → `/en/services/` |
| `bp_cpt_single_url($post, $slug)` | Translated CPT single URL | `bp_cpt_single_url($post, 'diensten')` → `/en/services/my-post/` |
| `bp_nav_item_url($item)` | Translated URL for a nav menu item | Used in walker classes |
| `bp_lang_url($lang)` | Language switcher URL for current page | `bp_lang_url('en')` → `/en/over-ons/` |

### Theme string translation

```php
// In templates:
echo bp__('Zoeken');  // "Search" when lang=en

// Defaults in inc/translations.php, overridable in admin panel
// Admin: Appearance > Translations
```

### Future improvements

- Refactor to a `BP_Router` class that encapsulates all routing logic
- Add translated post slugs for CPT singles (currently only archive slugs are translated)
- Consider caching the `get_posts()` call in rewrite rules for performance
