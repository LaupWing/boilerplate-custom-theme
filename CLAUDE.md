# Project Instructions

## Core Rule
**ALWAYS explain what you are about to do BEFORE doing it, and wait for user confirmation.** Do NOT use plan mode. Just explain conversationally and wait for a "yes" or confirmation before proceeding.

## Documentation
- **Changelog:** After making changes, always create or update `docs/changelog/YYYY-MM-DD.md` with a summary of what was done. One file per day — append if it already exists. Keep entries concise.
- **Todo:** When new tasks or ideas come up, add them to `docs/todo/YYYY-MM-DD.md`. Split into "Next up" (priority) and "Future" (parked).

## Block Development
- When creating content blocks (blocks that display page content like text, headings, articles), always add `data-seo-content` to the outer `<section>` tag in `render.php`. This tells Snel SEO which parts of the page contain real content for AI-powered SEO generation. Layout blocks (topbar, navbar, footer) should NOT have this attribute.

## Naming Convention

All Snelstack themes follow this rule:

- **Text domain:** Always `'snel'` — used in `__()`, `esc_html__()`, etc.
- **Function prefix:** `snel_` — for all shared helpers (translations, language, contact, etc.)
- **CSS class prefix:** `snel-` — for all shared component classes
- **JS globals:** `snelTranslate`, `snelSearch`, etc.
- **Theme hooks only:** Use the theme name for WordPress hooks that are theme-specific. E.g. `droneconsultancy_setup()`, `antiquewarehouse_scripts()`. These are the `add_action`/`add_filter` callbacks in `functions.php` for setup, scripts, block registration.
- **Block prefix:** `snel/` — all Gutenberg blocks use the `snel` namespace in `block.json`
- **Option names:** `snel_` prefix for WP options (e.g. `snel_theme_translations`)

**Why:** This keeps the shared Snelstack system (translations, SEO, settings) portable across all projects. Only the theme-specific hooks use the project name.

## Snel SEO Plugin Integration
When the Snel SEO plugin is installed, the theme must provide two filter hooks in `inc/translations/language.php` to connect the language system:
- `snel_seo_languages` — returns available languages from the theme's config
- `snel_seo_current_language` — returns `LocaleManager::current()`

See the bottom of `inc/translations/language.php` for the reference implementation.

## Project Overview
- Boilerplate WordPress theme for starting new client projects.
- Base framework with multilingual system, SEO, Tailwind CSS, and custom Gutenberg blocks.
