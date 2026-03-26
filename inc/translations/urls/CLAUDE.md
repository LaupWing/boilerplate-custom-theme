# URL Generation

## UrlGenerator.php

Builds language-aware URLs for all URL types on the site.

## URL Patterns

| Default lang (NL) | Non-default (EN) | Method |
|---|---|---|
| `/contact/` | `/en/contact/` | `url()` |
| `/over-ons/` | `/en/about-us/` | `pageUrl('over-ons')` |
| `/diensten/` | `/en/services/` | `cptUrl('diensten')` |
| `/diensten/my-post/` | `/en/services/my-post/` | `cptSingleUrl($post, 'diensten')` |
| `/` | `/en/` | `url('/')` |

## How Each Method Works

**`url($url)`** — Simple prefix
- Default lang: returns unchanged
- Other lang: prepends `/{lang}/`
- Checks for double-prefixing (won't add `/en/` if URL already has it)
- Handles full URLs (scheme + host) and relative paths

**`pageUrl($default_slug)`** — Page with translated slug
- Looks up `_slug_{lang}` post meta on the page
- If found: `/{lang}/{translated_slug}/`
- If not: `/{lang}/{default_slug}/` (uses Dutch slug as fallback)

**`cptUrl($cpt_slug)`** — CPT archive
- Reads slug translation from `config/slugs-cpt.php`
- Example config: `['diensten' => ['en' => 'services', 'de' => 'dienstleistungen']]`
- Falls back to Dutch slug if no translation configured

**`cptSingleUrl($post, $cpt_slug)`** — CPT single post
- Translates both archive segment (from config) and post slug (from `_slug_{lang}` meta)
- Example: `/en/services/my-translated-post/`

**`navItemUrl($item)`** — Navigation menu items
- Pages: uses `pageUrl()` with translated slug
- Front page: uses `url('/')`
- CPT singles: uses `cptSingleUrl()`
- Everything else: uses `url()` as fallback

**`langUrl($target_lang)`** — Language switcher
- Detects what the current page is (front page, page, post, CPT single, CPT archive)
- Builds the equivalent URL in the target language
- Handles translated slugs for pages and CPT archives
- Fallback: strips current lang prefix, adds target lang prefix

## Config: slugs-cpt.php

```php
return [
    'diensten' => [
        'en' => 'services',
        'de' => 'dienstleistungen',
        'fr' => 'services',
    ],
];
```

The keys are the WP post type names (which are also the Dutch slugs). The values are the translated slugs per language.

## Adding a New CPT with Translated Slugs

1. Add the post type slug mapping to `config/slugs-cpt.php`
2. The Router automatically picks it up and registers rewrite rules
3. The UrlGenerator automatically uses it for URL building
4. Flush rewrite rules (Settings → Permalinks → Save)
