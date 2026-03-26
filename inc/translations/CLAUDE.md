# Translation System (Snel Translations)

Portable multilingual system — copy to any WordPress theme.

## Architecture

```
config/
  languages.php           # Supported languages: code, label, locale, default flag
  slugs-cpt.php           # CPT slug translations per language
core/
  LocaleManager.php       # Language detection from URL
  Router.php              # Rewrite rules + slug resolution
  Translator.php          # Theme string lookups + multilingual value extraction
urls/
  UrlGenerator.php        # Build language-aware URLs
seo/
  SeoManager.php          # hreflang, canonical, html lang, title, meta desc
admin/
  admin-translations.php  # Admin page for managing translations
  AdminMetaBox.php        # Post editor meta box: slug, title, meta desc per lang
  translate.php           # AI translation via OpenAI (AJAX)
language.php              # Entry point — loads everything, defines helper functions
translations.php          # Default theme string translations (grouped by section)
```

## How It Works

1. Non-default languages get a URL prefix: `/en/page/`, `/de/page/`
2. Default language (NL) has no prefix: `/page/`
3. Router registers rewrite rules that set a `lang` query var
4. LocaleManager reads `lang` to determine current language
5. Translator/UrlGenerator/SeoManager all use LocaleManager to resolve the right language

## Data Storage

| Data | Storage | Example |
|------|---------|---------|
| Theme strings | `wp_option 'snel_theme_translations'` | `{'Zoeken' => {'en' => 'Search'}}` |
| Post titles | `_title_{lang}` post meta | `_title_en = 'About us'` |
| Post slugs | `_slug_{lang}` post meta | `_slug_en = 'about-us'` |
| Post meta desc | `_meta_desc_{lang}` post meta | `_meta_desc_en = '...'` |
| Term names | `_name_{lang}` term meta | `_name_en = 'Furniture'` |
| Term descriptions | `_desc_{lang}` term meta | `_desc_en = '...'` |
| Multilingual arrays | Single meta key with lang-keyed array | `{'nl' => '...', 'en' => '...'}` |

Default language values live in WP core fields (`post_title`, `term.name`), NOT in meta.

## Helper Functions

| Function | Purpose |
|----------|---------|
| `snel__($text)` | Translate a theme string |
| `snel_val($val)` | Extract current lang from multilingual array |
| `snel_attr($attributes, $key)` | Translate a block attribute |
| `snel_url($url)` | Add language prefix to URL |
| `snel_lang_url($lang)` | URL for switching language |
| `snel_term_name($term)` | Translated term name |
| `snel_term_desc($term)` | Translated term description |
| `snel_product_title($post_id)` | Translated post/CPT title |
| `snel_cpt_field($post_id, $key)` | Translated CPT meta field |

## Snel SEO Integration

Two filters connect to the Snel SEO plugin:
- `snel_seo_languages` — provides language list
- `snel_seo_current_language` — provides current language

## Rules

- Add new languages in `config/languages.php` only
- Add CPT slug translations in `config/slugs-cpt.php` only
- All `$_POST` data: always `wp_unslash()` before `sanitize_text_field()`
- Theme string lookup is case-insensitive with HTML entity decoding
