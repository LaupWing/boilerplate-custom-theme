# Boilerplate Custom Theme — SOP

Standard Operating Procedure for setting up a new WordPress project from this boilerplate.

---

## Quick Start

```bash
# 1. Copy the theme folder to your new project
# 2. Find-and-replace these prefixes:
#    - "snel" → "yourproject" (in PHP, package.json, style.css)
#    - "snel_" → "yp_" (PHP function prefix)
#    - "snel-" → "yp-" (CSS/JS handles)
#    - "snelTranslate" → "ypTranslate" (JS global)
#    - "SNEL_OPENAI_API_KEY" → "YP_OPENAI_API_KEY" (wp-config constant)
# 3. Install dependencies
npm install --include=dev
# 4. Build once
npm run build:css
# 5. Activate theme in WordPress
# 6. Flush rewrite rules: Settings > Permalinks > Save (click save without changing anything)
```

---

## Project Structure

```
theme-root/
├── style.css                          ← Theme metadata (name, version, author)
├── functions.php                      ← Theme setup, enqueues, loads inc/ files
│
├── header.php                         ← HTML head, site header, nav
├── footer.php                         ← Site footer, wp_footer()
├── front-page.php                     ← Homepage (renders block content)
├── page.php                           ← Generic page template
├── single.php                         ← Single blog post
├── index.php                          ← Fallback post listing
├── archive.php                        ← Post archive
├── search.php                         ← Search results
├── 404.php                            ← Error page
│
├── inc/                               ← PHP backend (mirrors src/ structure)
│   ├── translations/                  ← Translation system
│   │   ├── config/                    ← ✏️ EDIT PER PROJECT
│   │   │   ├── languages.php          ← Supported languages
│   │   │   └── slugs-cpt.php         ← CPT URL slug translations
│   │   ├── language.php               ← Core routing + helpers (DO NOT EDIT)
│   │   ├── translate.php              ← AI translation endpoint (DO NOT EDIT)
│   │   ├── translations.php          ← ✏️ Static theme string translations
│   │   └── admin-health-check.php    ← Missing translation checker (DO NOT EDIT)
│   ├── admin/                         ← Admin modules (auto-loaded via index.php)
│   │   └── seo/                       ← SEO module
│   │       ├── index.php              ← Entry point + React admin page registration
│   │       ├── sitemap.php            ← XML sitemap
│   │       ├── structured-data.php    ← JSON-LD structured data
│   │       └── open-graph.php         ← Open Graph meta tags
│   └── api/                           ← REST/AJAX endpoints (auto-loaded via index.php)
│
├── src/                               ← Source files (compiled to build/)
│   ├── index.css                      ← Tailwind directives + base styles
│   ├── editor.css                     ← Block editor styles (if needed)
│   ├── blocks/                        ← Custom Gutenberg blocks (auto-discovered)
│   │   └── components/                ← Shared block components
│   │       ├── TranslatableWrapper.js       ← Language toggle UI (DO NOT EDIT)
│   │       └── lang-helpers.js        ← getLang/setLang/translate (DO NOT EDIT)
│   ├── admin/                         ← Admin pages (React, auto-discovered)
│   │   └── seo/index.js              ← SEO admin page
│   └── editor/                        ← Gutenberg editor extensions (auto-discovered)
│       └── translator/index.js        ← Translation sidebar plugin
│
├── assets/
│   ├── js/main.js                     ← Frontend JavaScript
│   └── images/                        ← Theme images
│
├── build/                             ← Compiled output (gitignored)
├── node_modules/                      ← Dependencies (gitignored)
│
├── tailwind.config.js                 ← ✏️ Brand colors, fonts, animations
├── postcss.config.js                  ← PostCSS setup (DO NOT EDIT)
├── package.json                       ← Dependencies + build scripts
└── .gitignore
```

---

## What to Edit Per Project

### 1. Theme Identity

**`style.css`** — Update theme name, author, description.

**`package.json`** — Update name and description.

**`tailwind.config.js`** — Replace placeholder brand colors and add project fonts.

### 2. Languages

**`inc/translations/config/languages.php`**

Define which languages the project supports:

```php
return [
    'nl' => ['label' => 'NL', 'locale' => 'nl_NL', 'default' => true],
    'en' => ['label' => 'EN', 'locale' => 'en_US'],
    // Add more: 'de' => ['label' => 'DE', 'locale' => 'de_DE'],
];
```

### 3. CPT Slug Translations

**`inc/translations/config/slugs-cpt.php`**

When you register a Custom Post Type, add its translated slug here:

```php
return [
    'producten' => ['en' => 'products'],
    'diensten'  => ['en' => 'services'],
];
```

### 4. Theme String Translations

**`inc/translations/translations.php`**

Every time you use `snel__('Dutch text')` in a template, add the translation here:

```php
return [
    'Navigation' => [
        'Home' => ['en' => 'Home'],
    ],
    'Your Section' => [
        'Dutch text' => ['en' => 'English text'],
    ],
];
```

### 5. Page Slug Translations

Done in WordPress admin — each page has a "Translated URL Slugs" meta box in the sidebar. Fill in the English slug and save.

### 6. OpenAI API Key (for AI translation)

Add to `wp-config.php`:

```php
define('SNEL_OPENAI_API_KEY', 'sk-your-key-here');
```

---

## Build Commands

```bash
npm run build         # Production build (CSS + blocks + admin + editor)
npm run build:css     # Build Tailwind CSS only
npm run build:blocks  # Build Gutenberg blocks only
npm run build:admin   # Build admin pages (auto-discovers src/admin/*)
npm run build:editor  # Build editor extensions (auto-discovers src/editor/*)
npm run start         # Dev mode (watches all)
npm run watch:css     # Watch Tailwind CSS only
```

### How auto-discovery works

- **Blocks:** `--webpack-src-dir` scans `src/blocks/` for subdirectories with `block.json`
- **Admin & Editor:** A shell loop scans `src/admin/*/` and `src/editor/*/` for `index.js` files

To add a new admin page or editor extension, just create a folder with an `index.js`:

```bash
src/admin/my-feature/index.js   → builds to build/admin/my-feature/
src/editor/my-plugin/index.js   → builds to build/editor/my-plugin/
```

No need to update `package.json` — it's picked up automatically.

### PHP auto-loading

Any `inc/*/index.php` file is loaded automatically by `functions.php`. To add a new PHP module, create `inc/my-module/index.php` — no need to add a `require` in `functions.php`.

---

## Translation System — How It Works

### Three types of translated content

| Type | Where it lives | How to add |
|------|---------------|------------|
| **Theme strings** | `translations.php` + database | Use `snel__('Dutch text')` in templates, add translation to file |
| **Block content** | Block attributes `{nl: '...', en: '...'}` | Use TranslatableWrapper in block, toggle language, type or auto-translate |
| **Page URL slugs** | Post meta `_slug_en` | Fill in the meta box on each page in wp-admin |

### Fallback behavior

Nothing breaks if a translation is missing. The system always falls back to the Dutch (default) version:

- Missing theme string → shows Dutch text
- Missing block translation → shows Dutch content
- Missing page slug → uses Dutch slug in URL (`/en/over-ons/` instead of `/en/about-us/`)
- Missing CPT slug → uses Dutch slug in URL

### Translation priority for theme strings

1. Database (admin override) — highest priority
2. `translations.php` file — developer defaults
3. Original Dutch text — fallback

### Health check

Go to **Tools > Translation Check** in wp-admin to see all missing translations at a glance.

---

## Creating a Custom Gutenberg Block

### 1. Create the block folder

```
src/blocks/my-block/
├── block.json       ← Block metadata + attributes
├── index.js         ← Register block
├── edit.js          ← Editor UI
└── render.php       ← Frontend output (server-side)
```

### 2. block.json

```json
{
    "apiVersion": 3,
    "name": "snel/my-block",
    "title": "My Block",
    "category": "theme",
    "attributes": {
        "heading": { "type": "object", "default": {} },
        "content": { "type": "object", "default": {} }
    },
    "supports": { "html": false },
    "editorScript": "file:./index.js",
    "render": "file:./render.php"
}
```

Multilingual attributes use `"type": "object"` to store `{nl: '...', en: '...'}`.

### 3. edit.js

```jsx
import { useBlockProps } from '@wordpress/block-editor';
import { RichText } from '@wordpress/block-editor';
import TranslatableWrapper from '../components/TranslatableWrapper';
import { getLang, setLang, translateTexts } from '../components/lang-helpers';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { heading, content } = attributes;

    const handleTranslate = async (targetLang) => {
        const texts = [
            getLang(heading, 'nl'),
            getLang(content, 'nl'),
        ].filter(Boolean);

        const tr = await translateTexts(texts, targetLang);

        let i = 0;
        const updates = {};
        if (getLang(heading, 'nl')) updates.heading = setLang(heading, targetLang, tr[i++]);
        if (getLang(content, 'nl')) updates.content = setLang(content, targetLang, tr[i++]);
        setAttributes(updates);
    };

    return (
        <TranslatableWrapper
            blockProps={blockProps}
            label="My Block"
            onTranslate={handleTranslate}
        >
            {({ currentLang }) => (
                <div>
                    <RichText
                        tagName="h2"
                        value={getLang(heading, currentLang)}
                        onChange={(v) => setAttributes({ heading: setLang(heading, currentLang, v) })}
                        placeholder="Heading..."
                    />
                    <RichText
                        tagName="p"
                        value={getLang(content, currentLang)}
                        onChange={(v) => setAttributes({ content: setLang(content, currentLang, v) })}
                        placeholder="Content..."
                    />
                </div>
            )}
        </TranslatableWrapper>
    );
}
```

### 4. render.php

```php
<?php
$heading = snel_attr($attributes, 'heading');
$content = snel_attr($attributes, 'content');
?>
<section data-seo-content class="my-block">
    <?php if ($heading) : ?>
        <h2><?php echo esc_html($heading); ?></h2>
    <?php endif; ?>
    <?php if ($content) : ?>
        <div><?php echo wp_kses_post($content); ?></div>
    <?php endif; ?>
</section>
```

**Important:** Add `data-seo-content` to the outer `<section>` tag on any block that contains page content. This tells Snel SEO which sections to use for AI-powered SEO generation. Do NOT add it to layout blocks (topbar, navbar, footer).

### 5. index.js

```js
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';

registerBlockType(metadata.name, {
    edit: Edit,
});
```

### 6. Build and test

```bash
npm run start    # Dev mode — auto-rebuilds on changes
```

The block auto-registers via `functions.php` (scans `build/blocks/` for `block.json` files).

---

## Adding a Custom Post Type

### 1. Create `inc/cpt/your-cpt.php`

```php
<?php
function snel_register_your_cpt() {
    register_post_type('your-cpt', [
        'labels' => [...],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'dutch-slug', 'with_front' => false],
        'supports' => ['title', 'thumbnail'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-archive',
    ]);
}
add_action('init', 'snel_register_your_cpt');
```

### 2. Add slug translation in `config/slugs-cpt.php`

```php
'dutch-slug' => ['en' => 'english-slug'],
```

### 3. Require in `functions.php`

```php
require get_template_directory() . '/inc/cpt/your-cpt.php';
```

### 4. Flush rewrite rules

Go to **Settings > Permalinks > Save** (just click save, don't change anything).

### 5. Create templates (optional)

- `single-your-cpt.php` — single item template
- `archive-your-cpt.php` — archive/listing template

---

## Per-Project Checklist

When starting a new project from this boilerplate:

- [ ] Copy theme folder, rename it
- [ ] Find-and-replace all prefixes (snel → yourproject, snel_ → yp_, etc.)
- [ ] Update `style.css` theme name and author
- [ ] Update `package.json` name
- [ ] Set brand colors in `tailwind.config.js`
- [ ] Set Google Fonts in `functions.php` (or remove if not needed)
- [ ] Configure languages in `config/languages.php`
- [ ] Add `SNEL_OPENAI_API_KEY` to `wp-config.php` (for AI translation)
- [ ] Run `npm install && npm run build`
- [ ] Activate theme
- [ ] Flush rewrite rules (Settings > Permalinks > Save)
- [ ] Set up menus (Appearance > Menus)
- [ ] Set homepage (Settings > Reading > Static page)
- [ ] Check Tools > Translation Check for missing translations
- [ ] Install and activate Snel SEO plugin
- [ ] Add `SNEL_SEO_OPENAI_KEY` to `wp-config.php` (or reuse `SNEL_OPENAI_API_KEY`)

---

## Snel SEO Plugin Integration

The theme integrates with the **Snel SEO** plugin via two WordPress filter hooks in `inc/translations/language.php`. This allows the SEO plugin to handle multilingual meta titles and descriptions per language.

### Filter hooks

```php
// Provide available languages to Snel SEO
add_filter( 'snel_seo_languages', function () { ... } );

// Tell Snel SEO what language the visitor is viewing
add_filter( 'snel_seo_current_language', function () { ... } );
```

### How it works
- Snel SEO stores SEO titles and meta descriptions as JSON objects: `{ nl: "...", en: "...", de: "..." }`
- On the frontend, it reads the current language from the theme and serves the right meta tags
- In the editor, language buttons let you set SEO data per language
- AI generation creates content in the selected language
- If Snel SEO is not installed, the hooks are ignored — no errors

### Setup
1. Install and activate the Snel SEO plugin
2. The filter hooks connect automatically — no configuration needed
3. Go to **Snel SEO → Settings** to configure site-wide defaults
4. Per-page SEO is set in the Snel SEO meta box below the editor
