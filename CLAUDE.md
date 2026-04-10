# Project Instructions

## Core Rule
**ALWAYS explain what you are about to do BEFORE doing it, and wait for user confirmation.** Do NOT use plan mode. Just explain conversationally and wait for a "yes" or confirmation before proceeding.

## Documentation
- **Changelog:** `docs/changelog/YYYY-MM-DD.md` — one file per day, append if exists.
- **Todo:** `docs/todo/YYYY-MM-DD.md` — "Next up" (priority) and "Future" (parked).
- **SOP:** `docs/SOP.md` — step-by-step for new projects. Read this first when starting fresh.

## Project Overview
Boilerplate WordPress theme for new client projects. Multilingual, SEO-ready, Tailwind CSS, custom Gutenberg blocks.

## Naming Convention
**Always use `snel` prefix. Never rename per project.**
- `snel_` functions, `snel/` blocks, `snel-` CSS classes, `'snel'` text domain
- Only theme-specific WP hooks use the project name (e.g. `projectname_setup()`)

## Gotchas
These are things that break if you do them the "obvious" way:

- **Editor fonts:** `add_editor_style()` with an external Google Fonts URL does NOT work. Use `src/editor.css` with `@import url(...)` instead. See SOP Phase 5.
- **Editor background:** Do NOT set `background` on `.editor-styles-wrapper` — it overrides the whole editor.
- **npm install:** Local by Flywheel sets `NODE_ENV=production`. Always use `npm install --include=dev`.
- **Content blocks:** Add `data-seo-content` to the outer `<section>` in `render.php`. Layout blocks (topbar, navbar, footer) should NOT have this.
- **SEO plugin hooks:** Theme must provide `snel_seo_languages` and `snel_seo_current_language` filters in `inc/translations/language.php`.
- **Fonts in 3 places:** Frontend enqueue, `src/editor.css`, and `src/shared/theme.css` CSS variables.
- **TranslatableWrapper languages:** NEVER hardcode languages in `TranslatableWrapper.js`. It reads from `window.snelTranslate.langs` and `window.snelTranslate.default` which come from the language config. If you see hardcoded `['nl', 'en']` or `useState('nl')`, it's wrong.

## Key Helpers
- `snel_business('logo_url')` — logo from Business Info settings, falls back to `assets/images/`
- `snel_business('name')`, `snel_business('email')`, `snel_business('instagram_url')`, etc.
- `snel__('key')` — translated theme string
- `snel_url('/path/')` — language-aware URL
- `snel_attr($attributes, 'key')` — get translated block attribute for current language
- `snel_title()` — translated post title (checks `_title_{lang}` meta)
- Footer logo: same `snel_business('logo_url')` with Tailwind `invert` class
