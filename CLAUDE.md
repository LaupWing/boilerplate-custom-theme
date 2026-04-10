# Project Instructions

## Core Rule
**ALWAYS explain what you are about to do BEFORE doing it, and wait for user confirmation.** Do NOT use plan mode. Just explain conversationally and wait for a "yes" or confirmation before proceeding.

## Documentation
- **Changelog:** After making changes, always create or update `docs/changelog/YYYY-MM-DD.md` with a summary of what was done. One file per day — append if it already exists. Keep entries concise.
- **Todo:** When new tasks or ideas come up, add them to `docs/todo/YYYY-MM-DD.md`. Split into "Next up" (priority) and "Future" (parked).

## Block Development
- When creating content blocks (blocks that display page content like text, headings, articles), always add `data-seo-content` to the outer `<section>` tag in `render.php`. This tells Snel SEO which parts of the page contain real content for AI-powered SEO generation. Layout blocks (topbar, navbar, footer) should NOT have this attribute.

## Naming Convention

**IMPORTANT: Always use the `snel` prefix for ALL new projects. Never rename it per project.**

- **Text domain:** Always `'snel'` — used in `__()`, `esc_html__()`, etc.
- **Function prefix:** `snel_` — for all shared helpers (translations, language, contact, etc.)
- **CSS class prefix:** `snel-` — for all shared component classes
- **JS globals:** `snelTranslate`, `snelSearch`, etc.
- **Theme hooks only:** Use the theme name for WordPress hooks that are theme-specific. E.g. `locnguyen_setup()`, `antiquewarehouse_scripts()`. These are the `add_action`/`add_filter` callbacks in `functions.php` for setup, scripts, block registration.
- **Block prefix:** `snel/` — all Gutenberg blocks use the `snel` namespace in `block.json`
- **Option names:** `snel_` prefix for WP options (e.g. `snel_theme_translations`)

**Why:** This keeps the shared Snelstack system (translations, SEO, settings) portable across all projects. Only the theme-specific hooks use the project name. Do NOT rename `snel` to a project-specific prefix — it breaks portability.

## Snel SEO Plugin Integration
When the Snel SEO plugin is installed, the theme must provide two filter hooks in `inc/translations/language.php` to connect the language system:
- `snel_seo_languages` — returns available languages from the theme's config
- `snel_seo_current_language` — returns `LocaleManager::current()`

See the bottom of `inc/translations/language.php` for the reference implementation.

## Auto Title Translation
When a post/page is published and the `_title_{lang}` meta fields are empty, the system automatically translates the title via OpenAI and saves them.

**How it works** (`inc/translations/auto-slug.php`):
1. Fires on `save_post` (priority 20) for published, public post types
2. Checks if `_title_{lang}` is empty for each non-default language
3. If empty AND an OpenAI API key is configured (via Snelstack Settings), translates the post title
4. Saves to `_title_{lang}` post meta

**If no API key:** Does nothing — falls back to the original Dutch title.

**Manual override:** Users can edit titles in the Snel Stack editor sidebar (Translated Titles panel). Manual edits are never overwritten — auto-translate only fills empty fields.

## Key Translation Files
| File | Purpose |
|------|---------|
| `inc/translations/language.php` | Core: routing, helpers (snel__(), snel_attr(), snel_url()), SEO integration |
| `inc/translations/auto-slug.php` | Auto-translate slugs on publish |
| `inc/translations/admin/translate.php` | OpenAI AJAX endpoint for AI translations |
| `inc/translations/admin/admin-translations.php` | Admin page: React app, REST endpoints, settings |
| `inc/translations/core/LocaleManager.php` | Language config, detection, current language |
| `inc/translations/core/Router.php` | URL rewrite rules, slug resolution |
| `inc/translations/core/Translator.php` | Theme string translations, multilingual values |
| `inc/translations/urls/UrlGenerator.php` | Language-aware URL building |
| `inc/translations/seo/SeoManager.php` | hreflang, canonical, html lang attribute |
| `inc/translations/config/languages.php` | Supported languages (edit per project) |
| `inc/translations/config/slugs-cpt.php` | CPT archive slug translations (edit per project) |
| `inc/translations/translations.php` | Default theme string translations (edit per project) |

## Editor Fonts
Google Fonts don't load in the Gutenberg editor via `add_editor_style()` with an external URL. Instead:
1. Create `src/editor.css` with `@import url('https://fonts.googleapis.com/css2?family=...')` and `.editor-styles-wrapper` font-family rules
2. Load it via `add_editor_style('src/editor.css')` in theme setup (before `build/index.css`)
3. Do NOT set `background` on `.editor-styles-wrapper` — let the editor keep its default white

## Business Info
`inc/admin/business/index.php` provides `snel_business()` helper for brand name, email, logo, and social links. Auto-loaded via `inc/admin/*/index.php` glob. Logo falls back to `assets/images/logo-*.svg` or `.png` when no upload is set. Use `snel_business('logo_url')` in header, and add Tailwind `invert` class for dark backgrounds (footer).

## Project Overview
- Boilerplate WordPress theme for starting new client projects.
- Base framework with multilingual system, SEO, Tailwind CSS, and custom Gutenberg blocks.
