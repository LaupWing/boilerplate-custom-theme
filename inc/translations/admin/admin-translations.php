<?php
/**
 * Translations Admin Page — React-powered
 *
 * Centralized page to view and manage all translations:
 * - Theme strings (header, footer, UI text)
 * - Menu items
 * - Page content (Gutenberg block overview)
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

// ─── Register Menu ──────────────────────────────────────────────────────────

function snel_translations_admin_menu()
{
    add_menu_page(
        __('Translations', 'snel'),
        __('Translations', 'snel'),
        'manage_options',
        'snel-translations',
        'snel_translations_page_render',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0Ij48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwIiB5MT0iMCIgeDI9IjEiIHkyPSIxIj48c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjM2I4MmY2Ii8+PHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjN2MzYWVkIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9InVybCgjZykiLz48cGF0aCBkPSJNNi41IDEzYS43LjcgMCAwIDEtLjU1LTEuMTRsNi45My03LjE0YS4zNS4zNSAwIDAgMSAuNi4zMkwxMi4xNCA5LjJhLjcuNyAwIDAgMCAuNjYuOTVoNC45YS43LjcgMCAwIDEgLjU1IDEuMTRsLTYuOTMgNy4xNGEuMzUuMzUgMCAwIDEtLjYtLjMybDEuMzQtNC4yMUEuNy43IDAgMCAwIDExLjQgMTN6IiBmaWxsPSIjZmZmIi8+PC9zdmc+',
        28
    );

    add_submenu_page(
        'snel-translations',
        __('All Translations', 'snel'),
        __('All Translations', 'snel'),
        'manage_options',
        'snel-translations'
    );

    add_submenu_page(
        'snel-translations',
        __('Settings', 'snel'),
        __('Settings', 'snel'),
        'manage_options',
        'snel-translations-settings',
        'snel_translations_settings_render'
    );
}
add_action('admin_menu', 'snel_translations_admin_menu');

// ─── REST API Endpoints ─────────────────────────────────────────────────────

add_action('rest_api_init', function () {
    // Get all theme string translations (grouped by section).
    register_rest_route('snel-translations/v1', '/theme-strings', array(
        'methods'             => 'GET',
        'callback'            => function () {
            return rest_ensure_response(Translator::grouped());
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ));

    // Save theme string translations.
    register_rest_route('snel-translations/v1', '/theme-strings', array(
        'methods'             => 'POST',
        'callback'            => function (WP_REST_Request $request) {
            $translations = $request->get_json_params();
            if (! is_array($translations)) {
                return new WP_Error('invalid_data', 'Expected an object of translations.', array('status' => 400));
            }

            foreach ($translations as $dutch_key => $langs) {
                if (! is_array($langs)) continue;
                foreach ($langs as $lang => $text) {
                    snel_save_translation(
                        sanitize_text_field($dutch_key),
                        sanitize_key($lang),
                        sanitize_text_field($text)
                    );
                }
            }

            return rest_ensure_response(array('success' => true));
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ));

    // Get all pages with their translatable blocks.
    register_rest_route('snel-translations/v1', '/pages', array(
        'methods'             => 'GET',
        'callback'            => function () {
            $pages = get_posts(array(
                'post_type'      => array('page', 'post'),
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            ));

            $langs       = snel_get_supported_langs();
            $default     = snel_get_default_lang();
            $non_default = array_values(array_diff($langs, array($default)));
            $result      = array();

            foreach ($pages as $page) {
                $blocks       = parse_blocks($page->post_content);
                $translatable = snel_translations_extract_blocks($blocks, $non_default, $default);

                // Count completeness.
                $total  = 0;
                $filled = 0;
                foreach ($translatable as $block) {
                    foreach ($block['attributes'] as $attr) {
                        $total += count($non_default);
                        foreach ($non_default as $lang) {
                            if (! empty($attr['values'][$lang])) {
                                $filled++;
                            }
                        }
                    }
                }

                $result[] = array(
                    'id'      => $page->ID,
                    'title'   => $page->post_title,
                    'editUrl' => get_edit_post_link($page->ID, 'raw'),
                    'blocks'  => $translatable,
                    'total'   => $total,
                    'filled'  => $filled,
                );
            }

            return rest_ensure_response($result);
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ));
});

// ─── Block Extraction Helper ─────────────────────────────────────────────────

function snel_translations_extract_blocks($blocks, $non_default, $default, &$result = array())
{
    foreach ($blocks as $block) {
        if (! empty($block['blockName']) && strpos($block['blockName'], 'snel/') === 0) {
            $attrs              = $block['attrs'] ?? array();
            $translatable_attrs = array();

            foreach ($attrs as $key => $value) {
                if (is_array($value) && ! isset($value[0])) {
                    $has_lang_keys = false;
                    foreach ($value as $k => $v) {
                        if (in_array($k, array_merge(array($default), $non_default), true) && is_string($v)) {
                            $has_lang_keys = true;
                            break;
                        }
                    }

                    if ($has_lang_keys) {
                        $translatable_attrs[] = array(
                            'key'    => $key,
                            'values' => $value,
                        );
                    }
                }
            }

            if (! empty($translatable_attrs)) {
                $result[] = array(
                    'name'       => $block['blockName'],
                    'label'      => str_replace('snel/', '', $block['blockName']),
                    'attributes' => $translatable_attrs,
                );
            }
        }

        if (! empty($block['innerBlocks'])) {
            snel_translations_extract_blocks($block['innerBlocks'], $non_default, $default, $result);
        }
    }

    return $result;
}

// ─── Menu Items Helper ───────────────────────────────────────────────────────

function snel_translations_get_menu_items()
{
    $locations = get_nav_menu_locations();
    $items     = array();
    $db        = get_option('snel_theme_translations', array());
    $langs     = array_diff(snel_get_supported_langs(), array(snel_get_default_lang()));

    // Get file translations for defaults.
    $file_translations = array();
    $translations_file = get_template_directory() . '/inc/translations/translations.php';
    if (file_exists($translations_file)) {
        $raw = require $translations_file;
        foreach ($raw as $section => $strings) {
            if (is_array($strings) && ! isset($strings['en'])) {
                $file_translations = array_merge($file_translations, $strings);
            }
        }
    }

    foreach ($locations as $location => $menu_id) {
        if (! $menu_id) continue;
        $menu_items = wp_get_nav_menu_items($menu_id);
        if (! $menu_items) continue;

        foreach ($menu_items as $menu_item) {
            $title        = $menu_item->title;
            $translations = array();

            foreach ($langs as $lang) {
                if (! empty($db[$title][$lang])) {
                    $translations[$lang] = $db[$title][$lang];
                } elseif (! empty($file_translations[$title][$lang])) {
                    $translations[$lang] = $file_translations[$title][$lang];
                } else {
                    $translations[$lang] = '';
                }
            }

            $items[] = array(
                'id'           => $menu_item->ID,
                'title'        => $title,
                'translations' => $translations,
                'menu'         => $location,
                'menuName'     => wp_get_nav_menu_object($menu_id)->name ?? $location,
                'parent'       => (int) $menu_item->menu_item_parent,
            );
        }
    }

    return $items;
}

// ─── Enqueue React App ──────────────────────────────────────────────────────

add_action('admin_enqueue_scripts', function ($hook) {
    if (! in_array($hook, array('toplevel_page_snel-translations'), true)) return;

    $admin_dir  = get_template_directory() . '/build/admin/translations/';
    $admin_url  = get_template_directory_uri() . '/build/admin/translations/';
    $asset_file = $admin_dir . 'index.asset.php';
    if (! file_exists($asset_file)) return;

    $asset = require $asset_file;

    wp_enqueue_script('snel-translations-admin', $admin_url . 'index.js', $asset['dependencies'], $asset['version'], true);
    wp_enqueue_style('snel-translations-admin', $admin_url . 'index.css', array('wp-components'), $asset['version']);

    $languages    = snel_get_supported_langs();
    $default_lang = snel_get_default_lang();
    $config       = include get_template_directory() . '/inc/translations/config/languages.php';

    wp_localize_script('snel-translations-admin', 'snelTranslations', array(
        'restUrl'      => rest_url('snel-translations/v1'),
        'nonce'        => wp_create_nonce('wp_rest'),
        'languages'    => array_map(function ($code) use ($default_lang, $config) {
            return array(
                'code'    => $code,
                'label'   => $config[$code]['label'] ?? strtoupper($code),
                'default' => $code === $default_lang,
            );
        }, $languages),
        'defaultLang'  => $default_lang,
        'themeStrings' => Translator::grouped(),
        'menuItems'    => snel_translations_get_menu_items(),
        'menuEditUrl'  => admin_url('nav-menus.php'),
    ));
});

// ─── Render Page ────────────────────────────────────────────────────────────

function snel_translations_page_render()
{
    if (! current_user_can('manage_options')) return;
    echo '<div class="wrap"><div id="snel-translations-root"></div></div>';
}

// ─── Settings Page ──────────────────────────────────────────────────────────

function snel_translations_settings_render()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    // Handle save
    if (isset($_POST['snel_settings_nonce']) && wp_verify_nonce($_POST['snel_settings_nonce'], 'snel_settings_save')) {
        $api_key = sanitize_text_field(wp_unslash($_POST['snel_openai_api_key'] ?? ''));
        $model   = sanitize_text_field(wp_unslash($_POST['snel_openai_model'] ?? 'gpt-4o-mini'));

        update_option('snel_openai_api_key', $api_key, false);
        update_option('snel_openai_model', $model, false);

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'snel') . '</p></div>';
    }

    $api_key   = get_option('snel_openai_api_key', '');
    $model     = get_option('snel_openai_model', 'gpt-4o-mini');
    $has_const = defined('SNEL_OPENAI_API_KEY') && constant('SNEL_OPENAI_API_KEY') !== '';
    $config    = snel_get_languages_config();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Translation Settings', 'snel'); ?></h1>

        <form method="post">
            <?php wp_nonce_field('snel_settings_save', 'snel_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('AI Provider', 'snel'); ?></label>
                    </th>
                    <td>
                        <select disabled style="min-width: 200px;">
                            <option selected>OpenAI</option>
                        </select>
                        <p class="description"><?php esc_html_e('More providers can be added later.', 'snel'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="snel_openai_api_key"><?php esc_html_e('OpenAI API Key', 'snel'); ?></label>
                    </th>
                    <td>
                        <?php if ($has_const) : ?>
                            <input type="text" value="<?php esc_attr_e('Defined in wp-config.php', 'snel'); ?>" disabled class="regular-text" style="background: #f0f0f0;">
                            <p class="description" style="color: #00a32a;">
                                <?php esc_html_e('API key is set via SNEL_OPENAI_API_KEY constant in wp-config.php. That takes priority over this field.', 'snel'); ?>
                            </p>
                        <?php else : ?>
                            <input type="password"
                                   id="snel_openai_api_key"
                                   name="snel_openai_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text"
                                   placeholder="sk-..."
                                   autocomplete="off">
                            <button type="button" onclick="var f=document.getElementById('snel_openai_api_key');f.type=f.type==='password'?'text':'password';" class="button" style="vertical-align: top;">
                                <?php esc_html_e('Show/Hide', 'snel'); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e('Get your API key from platform.openai.com. Alternatively, define SNEL_OPENAI_API_KEY in wp-config.php.', 'snel'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="snel_openai_model"><?php esc_html_e('Model', 'snel'); ?></label>
                    </th>
                    <td>
                        <select id="snel_openai_model" name="snel_openai_model" style="min-width: 200px;">
                            <option value="gpt-4o-mini" <?php selected($model, 'gpt-4o-mini'); ?>>gpt-4o-mini (fast, cheap)</option>
                            <option value="gpt-4o" <?php selected($model, 'gpt-4o'); ?>>gpt-4o (best quality)</option>
                            <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo (legacy, cheapest)</option>
                        </select>
                        <p class="description"><?php esc_html_e('gpt-4o-mini is recommended for translations.', 'snel'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Languages', 'snel'); ?></label>
                    </th>
                    <td>
                        <?php foreach (snel_get_supported_langs() as $lang) :
                            $label      = $config[$lang]['label'] ?? strtoupper($lang);
                            $is_default = $lang === snel_get_default_lang();
                        ?>
                            <label style="display: inline-block; margin-right: 16px;">
                                <input type="checkbox" checked disabled>
                                <strong><?php echo esc_html($label); ?></strong><?php if ($is_default) {
                                    echo ' (source)';
                                } ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description" style="margin-top: 8px;">
                            <?php esc_html_e('To add/remove languages, edit inc/translations/config/languages.php.', 'snel'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'snel'); ?></th>
                    <td>
                        <?php
                        $effective_key = $has_const ? constant('SNEL_OPENAI_API_KEY') : $api_key;
                        if (! empty($effective_key)) :
                        ?>
                            <span style="color: #00a32a; font-weight: 600;">&#10003; <?php esc_html_e('API key configured — AI translation is active.', 'snel'); ?></span>
                        <?php else : ?>
                            <span style="color: #d63638; font-weight: 600;">&#10007; <?php esc_html_e('No API key — AI translation is disabled.', 'snel'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', 'snel')); ?>
        </form>
    </div>
    <?php
}
