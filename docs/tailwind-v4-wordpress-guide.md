# Tailwind CSS v4 + WordPress Gutenberg — Setup Guide

This documents the required setup and fixes for using Tailwind CSS v4 with WordPress custom themes and Gutenberg blocks. These are hard-won lessons — skip any of them and things will break silently.

## The Core Problem

Tailwind v4 puts all utilities inside CSS `@layer` declarations. WordPress admin CSS is **unlayered**. In the CSS cascade, unlayered styles **always beat layered styles**, regardless of specificity. This means WP's `p { font-size: 13px }` will override Tailwind's `.text-xs { font-size: var(--text-xs) }` in the editor.

This does NOT affect the frontend — only the Gutenberg editor.

## Required Setup

### 1. Dependencies

```json
{
  "devDependencies": {
    "@tailwindcss/cli": "^4.1.8",
    "@tailwindcss/postcss": "^4.1.8",
    "@wordpress/scripts": "^27.9.0",
    "tailwindcss": "^4.1.8"
  }
}
```

- `@tailwindcss/cli` — used by `build:css` script for frontend CSS
- `@tailwindcss/postcss` — used by wp-scripts (PostCSS) for editor/admin CSS
- `tailwindcss` — core library

### 2. PostCSS Config (theme root)

```js
// postcss.config.js
module.exports = {
  plugins: [require('@tailwindcss/postcss')],
};
```

wp-scripts uses this when building blocks and admin React apps.

### 3. Two Shared Theme Files

You need TWO versions of the shared theme — one for frontend, one for editor.

#### `src/shared/theme.css` (frontend — no !important)

```css
@import "tailwindcss";

@source "../blocks";
@source "../../inc";
@source "../../templates";
@source "../../template-parts";
@source "../../*.php";
@source "../../js";

@theme {
  --color-brand-example: #123456;
  --font-sans: 'Inter', sans-serif;
  /* ... your theme tokens ... */
}
```

#### `src/shared/editor-theme.css` (editor — WITH !important)

```css
@import "tailwindcss" important;

/* Same @source, @theme, and @keyframes as theme.css */
```

The `important` keyword adds `!important` to all generated utilities, which is the **only way** to beat WP's unlayered admin CSS in the editor.

#### Why two files?

- Frontend: `!important` is unnecessary and can cause specificity issues with inline styles, plugins, etc.
- Editor: without `!important`, Tailwind utilities in `@layer` lose to WP's admin CSS every time.

### 4. CSS Entry Points

#### Frontend: `src/index.css`

```css
@import "./shared/theme.css";
@plugin "@tailwindcss/typography";
/* ... your base styles, @layer base, pagination, etc. ... */
```

Built via CLI: `tailwindcss -i ./src/index.css -o ./build/index.css --minify`

#### Editor: block `editor.css` files

```css
@import "../../shared/editor-theme.css";
/* ... block-specific editor styles ... */
```

Built via wp-scripts (PostCSS pipeline).

### 5. `@source` Directives (Critical)

Tailwind v4 uses automatic content detection, but it scans from the CSS file's location. Since `theme.css` is in `src/shared/`, it won't find PHP templates or block files without explicit `@source` directives.

Without these, Tailwind won't generate classes used in your PHP templates — they'll silently be missing from the output.

### 6. Editor Styles Architecture

Not every block needs its own `editor.css`. Only ONE block needs to import the `editor-theme.css` — it provides Tailwind utilities for all blocks on the page.

Recommended approach:
- One block (e.g. `page-cards`) has an `editor.css` that imports the shared editor theme
- That block's `block.json` has `"editorStyle": "file:./index.css"`
- All other blocks get styled for free because WP loads all registered block CSS in the editor
- Shared focus/editable styles go in `editor-theme.css`, not per-block

## Migration Checklist (v3 → v4)

### Config Migration

- [ ] Delete `tailwind.config.js`
- [ ] Move colors, fonts, animations → `@theme { }` block in CSS
- [ ] `@tailwind base/components/utilities` → `@import "tailwindcss"`
- [ ] `require('@tailwindcss/typography')` → `@plugin "@tailwindcss/typography"`
- [ ] Add `@source` directives for all content directories
- [ ] Update `postcss.config.js`: `require('tailwindcss')` → `require('@tailwindcss/postcss')`

### Class Renames

- [ ] `flex-shrink-0` → `shrink-0`
- [ ] `flex-grow` → `grow`
- [ ] `flex-grow-0` → `grow-0`
- [ ] `overflow-ellipsis` → `text-ellipsis`
- [ ] `decoration-slice` → `box-decoration-slice`
- [ ] `decoration-clone` → `box-decoration-clone`

### Size Changes (visual review needed)

- `rounded-sm`: 2px (v3) → 4px (v4)
- `rounded`: 4px (v3) → 8px (v4)
- `shadow-sm`, `shadow`: slightly different values

Review all buttons, inputs, and cards after migration.

### Safelist

v4 has no `safelist` in CSS config. If you dynamically apply classes (e.g. via PHP/JS), ensure those classes appear somewhere in a scanned source file so Tailwind generates them. Alternatively, use `@source` to include the files that reference them.

## Gotchas

1. **Don't use `@import "tailwindcss" important` on the frontend** — it breaks inline styles, plugin CSS, and causes specificity wars.

2. **The editor iframe has its own document** — CSS variables defined in `:root` work because each block CSS includes its own copy of the theme variables.

3. **WP's `hidden` class conflicts with Tailwind's `hidden`** — both set `display: none`. In the editor, WP loads `common.min.css` which defines `.hidden { display: none }`. This is usually fine since they do the same thing, but be aware.

4. **Responsive classes don't work in the editor** — the editor iframe is narrower than the viewport. `hidden md:flex` will hide elements in the editor because the `md:` breakpoint doesn't trigger. Use different classes in `edit.js` vs `render.php`.

5. **Build order matters** — run `build:css` (CLI) before `build:blocks` (wp-scripts) if blocks reference the frontend stylesheet.
