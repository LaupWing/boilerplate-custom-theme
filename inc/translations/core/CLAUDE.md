# Core Classes

## LocaleManager.php

Single source of truth for language detection.

**Detection order:**
1. Override (set via `LocaleManager::setOverride()` — used by REST API rendering)
2. WordPress `lang` query var (set by Router rewrite rules)
3. First URL path segment (fallback for 404s)
4. Default language from config

**Key methods:**
- `config()` — full language config array from `config/languages.php`
- `supported()` — array of language codes `['nl', 'en', 'de']`
- `default()` — default language code (the one with `'default' => true`)
- `current()` — current language for this request
- `is($lang)` — check if current language matches
- `setOverride($lang)` — temporarily force a language

## Router.php

Registers WordPress rewrite rules so `/en/page/` URLs work.

**What it registers:**
1. `lang` query var
2. Homepage rules: `/en/` → `lang=en`
3. CPT rules from `config/slugs-cpt.php`: `/en/services/` → `post_type=diensten&lang=en`
4. Explicit page rules for pages with `_slug_{lang}` meta (uses `page_id` to bypass WP slug lookup)
5. Catch-all: `/en/anything/` → `pagename=anything&lang=en`

**Slug resolution (`resolveSlug`):**
When the catch-all sets `pagename=about-us` but the real WP slug is `over-ons`:
1. Looks up `_slug_{lang}` meta to find the page
2. Swaps `pagename` to the real slug
3. Also handles nested pages (`parent/child`) segment by segment
4. Also checks blog posts

**Why `page_id` instead of `pagename` for explicit rules:**
WordPress's `parse_request()` looks up pages by slug. If `pagename=about-us` but no page has that WP slug, WP silently converts it to `attachment=about-us` → 404. Using `page_id` bypasses this.

**Canonical redirect prevention:**
WordPress tries to redirect `/en/page/` to `/page/` (the "real" URL). `preventCanonicalRedirect` returns `false` for non-default languages.

**Flush rewrite rules:**
Only happens on `after_switch_theme`. If you add new rewrite rules, go to Settings → Permalinks and save, or re-activate the theme.

## Translator.php

Handles theme string translations and multilingual value extraction.

**Theme string lookup (`translate()`):**
1. DB override (`snel_theme_translations` option) — case-insensitive via `findKey()`
2. File defaults (`translations.php`) — case-insensitive via `findKey()`
3. Original text (fallback)

For default language: checks DB for NL overrides (lets admin change Dutch text too).

**`findKey()` — case-insensitive lookup:**
- Exact match first (fast path)
- Then: `mb_strtolower()` + `html_entity_decode()` on both key and text
- Handles `&amp;` vs `&`, `&#8217;` vs `'`, etc.

**Multilingual value extraction:**
- `value($val)` — if array with lang keys, extract current lang. Falls back to default.
- `attr($attributes, $key)` — shorthand for block attributes
- `cptField($post_id, $key)` — read post meta, extract current lang
- `productTitle($post_id)` — `_title_{lang}` meta, falls back to `post_title`
- `termName($term)` — `_name_{lang}` meta, falls back to `term.name`
- `termDesc($term)` — `_desc_{lang}` meta, falls back to `term.description`

**Caching:**
- `$dbTranslations` — cached after first `get_option()` call
- `$fileTranslations` — cached after first `require` of translations.php
