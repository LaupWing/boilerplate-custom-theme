# New Project SOP

Step-by-step procedure for creating a new WordPress theme from the Snelstack boilerplate.

---

## Phase 1: Core Theme Files

**Goal:** Theme activates, page renders, Tailwind works.

### Files to create:

| File | Purpose |
|------|---------|
| `style.css` | Theme metadata (name, version, author, text domain: `snel`) |
| `functions.php` | Theme setup + Tailwind enqueue + block registration |
| `header.php` | HTML head, opens body |
| `footer.php` | Closes body, wp_footer() |
| `index.php` | Fallback template (shows "theme is working") |
| `front-page.php` | Homepage (renders Gutenberg block content) |
| `package.json` | npm deps: tailwindcss v4, @wordpress/scripts, lucide-react |
| `postcss.config.js` | PostCSS with @tailwindcss/postcss |
| `src/index.css` | Tailwind directives + base styles |
| `src/shared/theme.css` | Brand colors, fonts, animations (placeholder) |
| `.gitignore` | Ignore node_modules/ and build/ |

### Naming rules:
- **Theme-specific hooks:** `projectname_setup()`, `projectname_scripts()` — use project name
- **Everything else:** `snel_` prefix, `snel/` block namespace, `snel-` CSS classes — never change
- **Text domain:** Always `'snel'`

### functions.php structure (bare minimum):
```php
// Theme setup (projectname_setup)
// - title-tag, post-thumbnails, html5, nav menus, block styles, editor styles

// Enqueue styles (projectname_scripts)
// - Tailwind CSS from build/index.css

// Block category (snel_block_categories)
// Block registration (snel_register_blocks)
```

### Install & test:
```bash
npm install --include=dev
npm run build:css
```

Then activate theme in WordPress, visit site. You should see a blank page with Tailwind classes working.

### Test checklist:
- [ ] Theme activates without errors
- [ ] Homepage renders (blank or "theme is working")
- [ ] Tailwind classes apply (check brand colors in index.php)
- [ ] No console errors
- [ ] wp_head() and wp_footer() output correctly (check admin bar shows)

---

## Phase 2: Translation System

**Goal:** Multilingual routing works, admin translations page available.

### Files to copy from boilerplate:

| File/Dir | Purpose |
|----------|---------|
| `inc/translations/config/languages.php` | **EDIT** — set your project's languages |
| `inc/translations/config/slugs-cpt.php` | CPT slug translations (empty if not needed) |
| `inc/translations/core/LocaleManager.php` | Language detection from URL (DO NOT EDIT) |
| `inc/translations/core/Router.php` | Rewrite rules (DO NOT EDIT) |
| `inc/translations/core/Translator.php` | String lookups, multilingual values (DO NOT EDIT) |
| `inc/translations/urls/UrlGenerator.php` | Language-aware URL builder (DO NOT EDIT) |
| `inc/translations/seo/SeoManager.php` | hreflang, html lang (DO NOT EDIT) |
| `inc/translations/language.php` | Entry point, loads everything, helper functions (DO NOT EDIT) |
| `inc/translations/translations.php` | **EDIT** — default theme string translations |
| `inc/translations/auto-translate.php` | AI auto-translate titles on publish (DO NOT EDIT) |
| `inc/translations/admin/translate.php` | AJAX endpoint for AI translation (DO NOT EDIT) |
| `inc/translations/admin/admin-translations.php` | Admin translations page (DO NOT EDIT) |
| `src/admin/translations/` | React admin UI (copy entire directory) |

### Customize per project:

1. **`config/languages.php`** — set languages and default
2. **`translations.php`** — add theme strings (keys in default language)
3. **`language.php`** — update `auto-slug.php` reference to `auto-translate.php` if needed

### Update functions.php:

```php
// Add after ABSPATH check:
require get_template_directory() . '/inc/translations/language.php';

if (is_admin()) {
    require get_template_directory() . '/inc/translations/admin/admin-translations.php';
}
```

### Update package.json:

Add `build:admin` and `start:admin` scripts for React compilation.

### Install & test:

```bash
npm run build:admin
```

Then visit wp-admin — you should see "Snel Translations" in the sidebar.

### Test checklist:
- [ ] Visit site normally → default language
- [ ] Visit `/nl/` (or non-default prefix) → language switches
- [ ] `snel__('key')` returns translated string
- [ ] Admin sidebar shows "Snel Translations"
- [ ] Theme strings tab shows all strings from translations.php
- [ ] Menu tab shows navigation items
- [ ] Settings page works (API key, model selection)
- [ ] Flush permalinks: Settings > Permalinks > Save

---

## Phase 3: Design & Blocks

**Goal:** Convert reference design into Gutenberg blocks.

*Coming soon — add as we build it.*

---

## Phase 4: SEO & Deployment

**Goal:** Snel SEO plugin integrated, production-ready.

*Coming soon — add as we build it.*
