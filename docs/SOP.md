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

### Automated checks (Claude Code should run these):

```bash
# PHP syntax check
php -l functions.php
php -l header.php
php -l footer.php
php -l index.php
php -l front-page.php

# CSS build must succeed
npm run build:css

# Verify output
ls build/index.css
```

### Manual test checklist:
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

// OpenAI helpers — REQUIRED before translation system and admin icons.
if (! function_exists('snelstack_get_openai_key')) {
    function snelstack_get_openai_key() {
        $key = get_option('snelstack_openai_key', '');
        if ($key) return $key;
        if (defined('SNEL_OPENAI_API_KEY') && constant('SNEL_OPENAI_API_KEY')) return constant('SNEL_OPENAI_API_KEY');
        return get_option('snel_openai_api_key', '');
    }
}
if (! function_exists('snelstack_get_openai_model')) {
    function snelstack_get_openai_model() {
        return get_option('snelstack_openai_model', get_option('snel_openai_model', 'gpt-4o-mini'));
    }
}

// Translation system
require get_template_directory() . '/inc/translations/language.php';

if (is_admin()) {
    require get_template_directory() . '/inc/translations/admin/admin-translations.php';
}

// Auto-load admin modules (icons, etc.)
foreach (glob(get_template_directory() . '/inc/admin/*/index.php') as $module_file) {
    require_once $module_file;
}
```

**IMPORTANT:** The `snelstack_get_openai_key()` and `snelstack_get_openai_model()` functions MUST be defined before loading any modules that use them (translations, admin icons, SEO). Without these, the Snelstack Settings page and AI features will fatal error.

### Also copy:

| File/Dir | Purpose |
|----------|---------|
| `inc/admin/snelstack/index.php` | Admin icon system + Snelstack Settings page |

### Update package.json:

Add `build:admin` and `start:admin` scripts for React compilation.

### Install & test:

```bash
npm run build:admin
```

Then visit wp-admin — you should see "Snel Translations" in the sidebar.

### Automated checks (Claude Code should run these):

```bash
# PHP syntax check — all translation files
find inc/translations/ -name "*.php" -exec php -l {} \;

# Admin React build must succeed
npm run build:admin

# Verify output
ls build/admin/translations/index.js
ls build/admin/translations/index.asset.php
```

### Manual test checklist:
- [ ] Visit site normally → default language
- [ ] Visit `/nl/` (or non-default prefix) → language switches
- [ ] `snel__('key')` returns translated string
- [ ] Admin sidebar shows "Snel Translations"
- [ ] Theme strings tab shows all strings from translations.php
- [ ] Menu tab shows navigation items
- [ ] Settings page works (API key, model selection)
- [ ] Flush permalinks: Settings > Permalinks > Save

---

## Phase 3: First Block + Block Components

**Goal:** Article section block works in editor with language switching and translation.

### Prerequisites:
- Phase 2 complete (translation system working)
- `npm run build:admin` successful

### Files to copy from boilerplate:

**Block components (shared, DO NOT EDIT):**

| File | Purpose |
|------|---------|
| `src/blocks/components/TranslatableWrapper.js` | Language toggle UI in block editor |
| `src/blocks/components/BgColorControl.js` | Background color picker for blocks |
| `src/blocks/components/InspectorOptions.js` | Shared inspector panel options |
| `src/blocks/components/lang-helpers.js` | `getLang()`, `setLang()`, `translateTexts()` |
| `src/blocks/components/content-extractor.js` | Extracts block content for SEO/AI |

**Shared CSS (IMPORTANT — blocks won't compile without these):**

| File | Purpose |
|------|---------|
| `src/shared/editor-theme.css` | Block editor styles for translatable blocks |

**NOTE:** TranslatableWrapper.js imports `editor-theme.css` and `InspectorOptions.js`. If you get "Module not found" errors during build, you're missing one of these files. Always copy ALL files from `src/blocks/components/` and `src/shared/`.

**First block — Article Section:**

| File | Purpose |
|------|---------|
| `src/blocks/article-section/block.json` | **EDIT** — update default language keys to match project |
| `src/blocks/article-section/index.js` | Block registration (DO NOT EDIT) |
| `src/blocks/article-section/edit.js` | Editor UI (DO NOT EDIT) |
| `src/blocks/article-section/render.php` | Frontend output (DO NOT EDIT) |

**Editor sidebar:**

| File | Purpose |
|------|---------|
| `src/editor/snelstack/index.js` | Snel Stack panel — translated titles + block translations |

### Customize per project:

1. **`block.json` attribute defaults** — change `{"nl": "", "en": ""}` to match your languages (e.g. `{"en": "", "nl": "", "es": ""}`)
2. **`edit.js` source language** — if default language is not `nl`, update `getLang(tagline, 'nl')` to your default lang code in the `handleTranslate` function

### Build & test:

```bash
npm run build    # builds CSS + blocks + admin + editor
```

### Automated checks (Claude Code should run these):

```bash
# PHP syntax check — all files must pass
find inc/ -name "*.php" -exec php -l {} \;

# Build must succeed without errors
npm run build

# Verify build output exists
ls build/blocks/article-section/block.json
ls build/editor/snelstack/index.js
ls build/index.css
```

### Manual test checklist:
- [ ] Article Section appears in block inserter under "Snel" category
- [ ] Language toggle shows in block toolbar
- [ ] Typing in EN, switching to NL shows empty fields (separate per language)
- [ ] "Translate" button translates content via AI
- [ ] Frontend renders correct language based on URL
- [ ] Snel Stack sidebar panel shows in editor

---

## Phase 4: Seeding

**Goal:** Demo pages with translated content for testing the full system.

### Files to create:

| File | Purpose |
|------|---------|
| `inc/seeders/seeder.php` | Admin page under Tools > Seed Content |
| `inc/seeders/seed-pages.php` | **EDIT** — page definitions with translated content |

### Update functions.php:

```php
if (is_admin()) {
    // ... existing admin requires ...
    require get_template_directory() . '/inc/seeders/seeder.php';
}
```

### Seeder supports:
- Pages and blog posts
- Article-section blocks with translated content per language
- Translated titles stored in `_title_{lang}` meta
- Translated slugs stored in `_slug_{lang}` meta
- Homepage set automatically via `is_front_page` flag
- Reset & reseed option (deletes all existing pages/posts first)
- Status table showing current pages/posts with slug translations

### Seed data files:
- **`seed-pages.php`** — returns array of page definitions. Use `locnguyen_seed_article()` helper (or create your own) to generate article-section block markup with translations.
- **`seed-posts.php`** — returns array of blog post definitions. Plain Gutenberg blocks (paragraphs, headings). Add `titles` key for translated post titles.

### Each seed item structure:
```php
[
    'title'         => 'Page Title',          // Default language title
    'slug'          => 'page-slug',           // WordPress slug
    'titles'        => ['nl' => '...', 'es' => '...'],  // Translated titles
    'slugs'         => ['nl' => '...', 'es' => '...'],  // Translated slugs (optional)
    'content'       => '<!-- wp:snel/article-section ... /-->',
    'is_front_page' => true,                  // Only one page should have this
]
```

### Update functions.php:
```php
if (is_admin()) {
    // ... existing admin requires ...
    require get_template_directory() . '/inc/seeders/seeder.php';
}
```

### Automated checks (Claude Code should run these):
```bash
# PHP syntax check
php -l inc/seeders/seeder.php
php -l inc/seeders/seed-pages.php
php -l inc/seeders/seed-posts.php
```

### Manual test checklist:
- [ ] Tools > Seed Content page appears in admin
- [ ] Clicking "Seed" creates pages and posts
- [ ] Pages have article-section blocks with content
- [ ] Blog posts created with translated titles
- [ ] Homepage is set correctly (Settings > Reading shows static page)
- [ ] Visiting `/nl/` or `/es/` shows translated content
- [ ] "Reset & Reseed" deletes and recreates everything
- [ ] Flush permalinks: Settings > Permalinks > Save

---

## Phase 5: Header, Footer & Language Switcher

**Goal:** Full navigation with language switching, blog listing, all templates working.

### Files to create/update:

| File | Purpose |
|------|---------|
| `header.php` | Nav links using `snel_url()`, language switcher using `snel_lang_url()` |
| `footer.php` | Footer with translated copyright text |
| `page.php` | Generic page template (renders block content) |
| `single.php` | Single post template with `snel_title()` |
| `index.php` | Blog listing with translated titles and "Read more" |

### Important notes:

**Google Fonts in editor:** Add `add_editor_style()` with your Google Fonts URL in the theme setup function. Without this, the block editor won't show the correct fonts.

```php
add_editor_style('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
```

**Blog page:** The seeder should create a Blog page with `'is_blog_page' => true`. The seeder sets `page_for_posts` option so WordPress uses it for the post listing. Add this to `seed-pages.php`.

**Nav links:** Use `snel_url('/slug/')` for all internal links. Use `snel__('Text')` for translatable nav labels. Use `snel_lang_url($lang)` for language switcher links.

**Translated titles:** Use `snel_title()` instead of `get_the_title()` in templates — it checks `_title_{lang}` meta and falls back to the original title.

### Automated checks (Claude Code should run these):

```bash
# PHP lint all templates
php -l header.php
php -l footer.php
php -l page.php
php -l single.php
php -l index.php

# Rebuild CSS (new templates may add Tailwind classes)
npm run build:css

# Verify output
ls build/index.css
```

### Manual test checklist:
- [ ] Header shows nav links and language switcher
- [ ] Clicking EN/NL/ES switches language in URL
- [ ] Nav text translates per language
- [ ] Footer copyright text translates
- [ ] Homepage renders block content
- [ ] About/Training/Contact pages render block content
- [ ] Blog page shows post listing with translated titles
- [ ] Single post page shows translated title and content
- [ ] "Read more" and "Back" text translates

---

## Phase 6: Design Conversion

**Goal:** Convert reference design (Next.js/Figma) into WordPress theme.

### Step 1: Update seed data to match design
- Update `seed-pages.php` to match the pages in the design
- Update menu items in seeder to match nav links
- Update `translations.php` to match new nav labels
- Reset & reseed to apply changes

### Step 2: Set brand colors and fonts
- Extract colors from the design's CSS/globals (look for CSS variables, Tailwind config, or globals.css)
- Update `src/shared/theme.css` with project-specific color tokens
- Update `src/index.css` base layer to use new color tokens
- Update Google Fonts URL in `functions.php` (both frontend enqueue AND `add_editor_style()`)
- Rebuild CSS: `npm run build:css`

**IMPORTANT:** Update fonts in TWO places:
1. `locnguyen_scripts()` — frontend Google Fonts enqueue
2. `locnguyen_setup()` — `add_editor_style()` for block editor

### Step 3: Convert header/navbar
- Study the design's navbar component (layout, logo, nav items, actions)
- Update `header.php` to match the design
- Keep `snel_url()` for links, `snel__()` for translations, `snel_lang_url()` for language switcher
- Rebuild CSS after adding new Tailwind classes

### Step 4: Convert footer
- Update `footer.php` to match the design
- Keep translated strings via `snel__()`

### Step 5: Convert page components to blocks
- Each major section in the design becomes a Gutenberg block
- Follow the article-section pattern: `block.json` + `index.js` + `edit.js` + `render.php`
- Use `TranslatableWrapper` for all translatable content
- Update `block.json` defaults to match project languages

### Automated checks (Claude Code should run these):
```bash
# Rebuild CSS after theme changes
npm run build:css

# Rebuild blocks if new blocks added
npm run build

# Verify CSS output
ls build/index.css
```

### Manual test checklist:
- [ ] Background color matches design
- [ ] Fonts render correctly (check both frontend and editor)
- [ ] Nav links and language switcher work
- [ ] Responsive layout matches design (mobile + desktop)
- [ ] Colors consistent across all pages

---

## Phase 7: SEO & Deployment

**Goal:** Snel SEO plugin integrated, production-ready.

*Coming soon — add as we build it.*

---

## Phase 4: SEO & Deployment

**Goal:** Snel SEO plugin integrated, production-ready.

*Coming soon — add as we build it.*
