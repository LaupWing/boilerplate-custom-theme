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

**Goal:** Multilingual routing works, language switcher renders.

*Coming soon — add as we build it.*

---

## Phase 3: Design & Blocks

**Goal:** Convert reference design into Gutenberg blocks.

*Coming soon — add as we build it.*

---

## Phase 4: SEO & Deployment

**Goal:** Snel SEO plugin integrated, production-ready.

*Coming soon — add as we build it.*
