# Admin Translation UI

## admin-translations.php

Admin page under "Translations" menu. Manages theme string translations.

**Tabs:**
- **Theme Strings** — edit translations from `translations.php` (grouped by section)
- **Menu** — edit navigation menu item translations
- **Settings** — OpenAI API key and model configuration

**Form handling:**
- Translation keys are base64-encoded in form names to avoid special character issues
- POST data → `wp_unslash()` → `sanitize_text_field()` → `Translator::save()`
- Menu items saved to nav menu item post meta

**Settings saved to:**
- `snel_openai_api_key` option
- `snel_openai_model` option

## AdminMetaBox.php

Meta box on the post/page editor for per-language SEO fields.

**Displays on:** all public post types (pages + custom post types)

**Fields per non-default language:**

| Field | Meta key | Purpose |
|-------|----------|---------|
| URL Slug | `_slug_{lang}` | Translated URL (e.g. "about-us" for English) |
| Title Tag | `_title_{lang}` | Document title for SEO |
| Meta Description | `_meta_desc_{lang}` | Meta description for SEO |

**Save flow:**
- `save_post` hook → reads `$_POST` → `wp_unslash()` → `sanitize_text_field()` → `update_post_meta()`
- Empty values → `delete_post_meta()` (clean up)

## translate.php

AI translation via OpenAI. AJAX endpoint for translating text arrays.

**AJAX action:** `wp_ajax_snel_translate`

**How it works:**
1. Receives array of texts + source lang + target lang
2. Builds a numbered list prompt
3. Calls OpenAI API (model from Snelstack settings, temperature 0.3)
4. Parses numbered response back into array
5. Returns `{translations: [...]}` JSON

**Frontend integration:**
- `snel_translate_editor_assets()` enqueues on block editor
- Localizes `window.snelTranslate` with: ajaxUrl, nonce, langs, default
- Also handles `?awScrollTo=snel/block-name` for scrolling to a specific block

**API key source:** `snelstack_get_openai_key()` function (from Snelstack plugin)

**Preserves:** HTML tags (`<strong>`, `<em>`, `<a>`) — uses `wp_kses_post()` for sanitization
