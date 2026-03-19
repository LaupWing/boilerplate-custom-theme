<?php

/**
 * Translations Admin Page
 *
 * Centralized page to view and manage all translations:
 * - Theme strings (header, footer, UI text)
 * - Menu items
 * - Page content (Gutenberg block overview)
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

// ---- Register Menu ---------------------------------------------------------

function bp_translations_admin_menu()
{
    add_menu_page(
        __('Translations', 'boilerplate'),
        __('Translations', 'boilerplate'),
        'manage_options',
        'bp-translations',
        'bp_translations_page_render',
        'dashicons-translation',
        30
    );

    add_submenu_page(
        'bp-translations',
        __('All Translations', 'boilerplate'),
        __('All Translations', 'boilerplate'),
        'manage_options',
        'bp-translations'
    );

    add_submenu_page(
        'bp-translations',
        __('Settings', 'boilerplate'),
        __('Settings', 'boilerplate'),
        'manage_options',
        'bp-translations-settings',
        'bp_translations_settings_render'
    );
}
add_action('admin_menu', 'bp_translations_admin_menu');

// ---- Handle Save -----------------------------------------------------------

function bp_translations_handle_save()
{
    if (! isset($_POST['bp_translations_nonce'])) {
        return;
    }
    if (! wp_verify_nonce($_POST['bp_translations_nonce'], 'bp_translations_save')) {
        return;
    }
    if (! current_user_can('manage_options')) {
        return;
    }

    $tab = sanitize_text_field($_POST['bp_tab'] ?? 'theme');

    if ($tab === 'theme' || $tab === 'menu') {
        $items = $_POST['tr'] ?? [];
        foreach ($items as $encoded_key => $langs) {
            $key = base64_decode($encoded_key);
            foreach ($langs as $lang => $text) {
                $lang = sanitize_text_field($lang);
                $text = sanitize_text_field($text);
                bp_save_translation($key, $lang, $text);
            }
        }
    }

    $redirect = add_query_arg([
        'page'  => 'bp-translations',
        'tab'   => $tab,
        'saved' => '1',
    ], admin_url('admin.php'));
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_init', 'bp_translations_handle_save');

// ---- Render Page -----------------------------------------------------------

function bp_translations_page_render()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $tab   = sanitize_text_field($_GET['tab'] ?? 'theme');
    $saved = isset($_GET['saved']);
    $langs = array_diff(bp_get_supported_langs(), [bp_get_default_lang()]);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Translations', 'boilerplate'); ?></h1>

        <?php if ($saved) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Translations saved.', 'boilerplate'); ?></p>
            </div>
        <?php endif; ?>

        <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=bp-translations&tab=theme')); ?>"
               class="nav-tab <?php echo $tab === 'theme' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Theme Strings', 'boilerplate'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bp-translations&tab=menu')); ?>"
               class="nav-tab <?php echo $tab === 'menu' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Menu', 'boilerplate'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bp-translations&tab=pages')); ?>"
               class="nav-tab <?php echo $tab === 'pages' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Pages (Content)', 'boilerplate'); ?>
            </a>
        </nav>

        <?php if ($tab === 'pages') : ?>
            <?php bp_translations_tab_pages($langs); ?>
        <?php else : ?>
        <form method="post" id="bp-translations-form">
            <?php wp_nonce_field('bp_translations_save', 'bp_translations_nonce'); ?>
            <input type="hidden" name="bp_tab" value="<?php echo esc_attr($tab); ?>">

            <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Save Translations', 'boilerplate'); ?>
                </button>
                <button type="button" id="bp-translate-all" class="button" style="display: flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-translation" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <?php esc_html_e('AI Translate All Missing', 'boilerplate'); ?>
                </button>
                <span id="bp-translate-status" style="color: #666;"></span>
            </div>

            <?php
            if ($tab === 'theme') {
                bp_translations_tab_theme($langs);
            } elseif ($tab === 'menu') {
                bp_translations_tab_menu($langs);
            }
            ?>
        </form>
        <?php endif; ?>
    </div>

    <?php bp_translations_page_script(); ?>
    <?php
}

// ---- Tab: Theme Strings ----------------------------------------------------

function bp_translations_tab_theme($langs)
{
    $grouped = bp_get_grouped_theme_translations();
    $total   = 0;
    ?>
    <?php foreach ($grouped as $section_name => $strings) :
        $total += count($strings);
    ?>
        <h3 style="margin-top: 24px; margin-bottom: 8px;"><?php echo esc_html($section_name); ?></h3>
        <table class="widefat striped" style="margin-bottom: 16px;">
            <thead>
                <tr>
                    <th style="width: 25%;">
                        <?php echo esc_html(strtoupper(bp_get_default_lang())); ?> (source)
                    </th>
                    <?php foreach ($langs as $lang) : ?>
                        <th><?php echo esc_html(strtoupper($lang)); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($strings as $nl_key => $translations) :
                    $encoded = base64_encode($nl_key);
                ?>
                    <tr>
                        <td>
                            <input type="hidden" class="bp-source-text" value="<?php echo esc_attr($nl_key); ?>">
                            <input type="text"
                                   name="tr[<?php echo esc_attr($encoded); ?>][<?php echo esc_attr(bp_get_default_lang()); ?>]"
                                   value="<?php echo esc_attr($translations[bp_get_default_lang()] ?? $nl_key); ?>"
                                   class="large-text"
                                   style="font-weight: 600;">
                        </td>
                        <?php foreach ($langs as $lang) :
                            $value = $translations[$lang] ?? '';
                        ?>
                            <td>
                                <input type="text"
                                       name="tr[<?php echo esc_attr($encoded); ?>][<?php echo esc_attr($lang); ?>]"
                                       value="<?php echo esc_attr($value); ?>"
                                       class="large-text bp-translation-input"
                                       data-lang="<?php echo esc_attr($lang); ?>"
                                       placeholder="<?php echo esc_attr($value ? '' : '— empty —'); ?>"
                                       style="<?php echo empty($value) ? 'border-color: #d63638; background: #fef7f7;' : ''; ?>">
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
    <p class="description" style="margin-top: 8px;">
        <?php printf(
            esc_html__('%d strings across %d sections. Empty fields are highlighted in red.', 'boilerplate'),
            $total,
            count($grouped)
        ); ?>
    </p>
    <?php
}

// ---- Tab: Menu -------------------------------------------------------------

function bp_translations_tab_menu($langs)
{
    $locations = get_nav_menu_locations();

    if (empty($locations['primary'])) {
        echo '<p>No primary menu assigned. Go to <a href="' . esc_url(admin_url('nav-menus.php')) . '">Appearance &gt; Menus</a> and assign a menu to the Primary location.</p>';
        return;
    }

    $menu_items = wp_get_nav_menu_items($locations['primary']);

    if (empty($menu_items)) {
        echo '<p>No menu items found.</p>';
        return;
    }

    $db = get_option('bp_theme_translations', []);
    ?>
    <h3 style="margin-top: 16px; margin-bottom: 8px;">Primary Menu</h3>
    <p style="color: #666; margin-bottom: 12px;">
        Add translations for each menu item. The <?php echo esc_html(strtoupper(bp_get_default_lang())); ?> column is the source title from Appearance &gt; Menus.
    </p>
    <table class="widefat striped">
        <thead>
            <tr>
                <th style="width: 25%;">
                    <?php echo esc_html(strtoupper(bp_get_default_lang())); ?> (source)
                </th>
                <?php foreach ($langs as $lang) : ?>
                    <th><?php echo esc_html(strtoupper($lang)); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menu_items as $item) :
                $label  = trim($item->title);
                $key    = base64_encode($label);

                // Calculate depth for indentation
                $depth  = 0;
                $parent = $item->menu_item_parent;
                while ($parent) {
                    $depth++;
                    $found = false;
                    foreach ($menu_items as $mi) {
                        if ((int) $mi->ID === (int) $parent) {
                            $parent = $mi->menu_item_parent;
                            $found  = true;
                            break;
                        }
                    }
                    if (! $found) {
                        break;
                    }
                }
                $indent = $depth * 24;
            ?>
                <tr>
                    <td style="<?php echo $indent ? 'padding-left: ' . (12 + $indent) . 'px;' : ''; ?>">
                        <?php if ($depth > 0) : ?>
                            <span style="color: #bbb; margin-right: 4px;">&mdash;</span>
                        <?php endif; ?>
                        <input type="hidden" name="tr[<?php echo esc_attr($key); ?>][<?php echo esc_attr(bp_get_default_lang()); ?>]" value="<?php echo esc_attr($label); ?>">
                        <strong style="font-size: 13px;"><?php echo esc_html($label); ?></strong>
                    </td>
                    <?php foreach ($langs as $lang) :
                        $value = $db[$label][$lang] ?? '';
                    ?>
                        <td>
                            <input type="text"
                                   name="tr[<?php echo esc_attr($key); ?>][<?php echo esc_attr($lang); ?>]"
                                   value="<?php echo esc_attr($value); ?>"
                                   class="large-text bp-translation-input"
                                   data-lang="<?php echo esc_attr($lang); ?>"
                                   placeholder="<?php echo esc_attr($label); ?>"
                                   <?php if (empty($value)) {
                                       echo 'style="border-color: #f0b849;"';
                                   } ?>>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// ---- Tab: Pages (Content) --------------------------------------------------

/**
 * Recursively extract translatable {lang: '...'} fields from block attributes.
 */
function bp_extract_translatable_fields($attrs, $prefix = '')
{
    $fields  = [];
    $default = bp_get_default_lang();
    $langs   = bp_get_supported_langs();

    foreach ($attrs as $key => $value) {
        $path = $prefix ? "{$prefix}.{$key}" : $key;

        // Direct translatable object: has default lang key + at least one other
        if (is_array($value) && isset($value[$default]) && ! isset($value[0])) {
            $has_lang_keys = false;
            foreach ($langs as $l) {
                if ($l !== $default && array_key_exists($l, $value)) {
                    $has_lang_keys = true;
                    break;
                }
            }
            if ($has_lang_keys) {
                $fields[] = ['key' => $path, 'value' => $value];
                continue;
            }
        }

        // Sequential array (slides, links, buttons)
        if (is_array($value) && isset($value[0]) && is_array($value[0])) {
            foreach ($value as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $nested = bp_extract_translatable_fields($item, "{$path}[{$i}]");
                $fields = array_merge($fields, $nested);
            }
        }
    }

    return $fields;
}

function bp_translations_tab_pages($langs)
{
    $pages = get_posts([
        'post_type'      => ['page', 'post'],
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ]);

    if (empty($pages)) {
        echo '<p>' . esc_html__('No published pages found.', 'boilerplate') . '</p>';
        return;
    }

    $default_lang = bp_get_default_lang();
    $page_data    = [];

    foreach ($pages as $page) {
        $blocks      = parse_blocks($page->post_content);
        $page_blocks = [];

        foreach ($blocks as $block) {
            if (empty($block['blockName']) || strpos($block['blockName'], 'boilerplate/') !== 0) {
                continue;
            }

            $fields = bp_extract_translatable_fields($block['attrs'] ?? []);
            if (empty($fields)) {
                continue;
            }

            $stats = [];
            foreach ($langs as $lang) {
                $filled = 0;
                $total  = 0;
                foreach ($fields as $field) {
                    $nl_val = trim($field['value'][$default_lang] ?? '');
                    if (empty($nl_val)) {
                        continue;
                    }
                    $total++;
                    $val = trim($field['value'][$lang] ?? '');
                    if (! empty($val)) {
                        $filled++;
                    }
                }
                $stats[$lang] = ['filled' => $filled, 'total' => $total];
            }

            $page_blocks[] = [
                'name'   => $block['blockName'],
                'title'  => str_replace('boilerplate/', '', $block['blockName']),
                'fields' => $fields,
                'stats'  => $stats,
            ];
        }

        if (! empty($page_blocks)) {
            $page_data[] = [
                'id'     => $page->ID,
                'title'  => $page->post_title,
                'slug'   => $page->post_name,
                'type'   => $page->post_type,
                'blocks' => $page_blocks,
            ];
        }
    }

    if (empty($page_data)) {
        echo '<p>' . esc_html__('No pages with translatable blocks found.', 'boilerplate') . '</p>';
        return;
    }
    ?>

    <p class="description" style="margin-bottom: 16px;">
        <?php esc_html_e('Read-only overview of Gutenberg block translations per page. Click "Edit" to open the page editor.', 'boilerplate'); ?>
    </p>

    <?php foreach ($page_data as $page) :
        $edit_url = admin_url('post.php?post=' . $page['id'] . '&action=edit');
    ?>
        <div style="margin-bottom: 24px; border: 1px solid #c3c4c7; background: #fff;">
            <div style="padding: 12px 16px; border-bottom: 1px solid #c3c4c7; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="font-size: 14px;"><?php echo esc_html($page['title']); ?></strong>
                    <span style="color: #787c82; margin-left: 8px;">/<?php echo esc_html($page['slug']); ?></span>
                </div>
                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                    <?php esc_html_e('Edit Page', 'boilerplate'); ?>
                </a>
            </div>
            <table class="widefat" style="border: 0; border-radius: 0;">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?php esc_html_e('Block', 'boilerplate'); ?></th>
                        <th style="width: 10%;"><?php echo esc_html(strtoupper($default_lang)); ?></th>
                        <?php foreach ($langs as $lang) : ?>
                            <th style="width: 10%;"><?php echo esc_html(strtoupper($lang)); ?></th>
                        <?php endforeach; ?>
                        <th style="width: 10%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($page['blocks'] as $bi => $block) :
                        $block_edit_url = add_query_arg('bpScrollTo', $block['name'], $edit_url);
                        $row_id = 'bp-detail-' . $page['id'] . '-' . $bi;

                        $nl_filled = 0;
                        $nl_total  = count($block['fields']);
                        foreach ($block['fields'] as $field) {
                            if (! empty(trim($field['value'][$default_lang] ?? ''))) {
                                $nl_filled++;
                            }
                        }
                    ?>
                        <tr class="bp-block-row" data-detail="<?php echo esc_attr($row_id); ?>" style="cursor: pointer;">
                            <td>
                                <span class="bp-toggle-arrow" style="display: inline-block; width: 16px; font-size: 11px; color: #787c82;">&#9654;</span>
                                <strong><?php echo esc_html(ucwords(str_replace('-', ' ', $block['title']))); ?></strong>
                                <br><small style="color: #787c82; margin-left: 16px;"><?php echo esc_html(count($block['fields'])); ?> translatable fields</small>
                            </td>
                            <td>
                                <span style="color: <?php echo $nl_filled === $nl_total ? '#00a32a' : '#d63638'; ?>; font-weight: 600;">
                                    <?php echo esc_html("{$nl_filled}/{$nl_total}"); ?>
                                </span>
                            </td>
                            <?php foreach ($langs as $lang) :
                                $stat        = $block['stats'][$lang];
                                $is_complete = $stat['total'] === 0 || $stat['filled'] === $stat['total'];
                            ?>
                                <td>
                                    <span style="color: <?php echo $is_complete ? '#00a32a' : '#d63638'; ?>; font-weight: 600;">
                                        <?php echo esc_html("{$stat['filled']}/{$stat['total']}"); ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="<?php echo esc_url($block_edit_url); ?>" class="button button-small" onclick="event.stopPropagation();">
                                    <?php esc_html_e('Edit', 'boilerplate'); ?> &rarr;
                                </a>
                            </td>
                        </tr>
                        <?php foreach ($block['fields'] as $field) :
                            $nl_val = trim($field['value'][$default_lang] ?? '');
                            $label  = preg_replace('/\[(\d+)\]/', '.$1', $field['key']);
                        ?>
                            <tr class="<?php echo esc_attr($row_id); ?>" style="display: none; background: #f9f9f9;">
                                <td style="padding-left: 32px;">
                                    <code style="font-size: 12px;"><?php echo esc_html($label); ?></code>
                                    <?php if ($nl_val) : ?>
                                        <br><small style="color: #50575e; max-width: 250px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo esc_html(wp_trim_words(wp_strip_all_tags($nl_val), 8, '...')); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (! empty($nl_val)) : ?>
                                        <span style="color: #00a32a;">&#10003;</span>
                                    <?php else : ?>
                                        <span style="color: #d63638;">&#10007;</span>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($langs as $lang) :
                                    $val = trim($field['value'][$lang] ?? '');
                                ?>
                                    <td>
                                        <?php if (empty($nl_val)) : ?>
                                            <span style="color: #787c82;">&mdash;</span>
                                        <?php elseif (! empty($val)) : ?>
                                            <span style="color: #00a32a;">&#10003;</span>
                                        <?php else : ?>
                                            <span style="color: #d63638;">&#10007;</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <p class="description">
        <?php printf(
            esc_html__('%d pages with %d translatable blocks.', 'boilerplate'),
            count($page_data),
            array_sum(array_map(function ($p) {
                return count($p['blocks']);
            }, $page_data))
        ); ?>
    </p>

    <script>
    (function() {
        document.querySelectorAll('.bp-block-row').forEach(function(row) {
            row.addEventListener('click', function() {
                var id = row.getAttribute('data-detail');
                var details = document.querySelectorAll('.' + id);
                var arrow = row.querySelector('.bp-toggle-arrow');
                var open = details[0] && details[0].style.display !== 'none';
                details.forEach(function(r) { r.style.display = open ? 'none' : 'table-row'; });
                if (arrow) arrow.innerHTML = open ? '&#9654;' : '&#9660;';
            });
        });
    })();
    </script>
    <?php
}

// ---- JavaScript: AI Translate All ------------------------------------------

function bp_translations_page_script()
{
    ?>
    <script>
    (function() {
        var btn    = document.getElementById('bp-translate-all');
        var status = document.getElementById('bp-translate-status');
        if (!btn) return;

        btn.addEventListener('click', function() {
            var targetLangs = <?php echo wp_json_encode(array_values(array_diff(bp_get_supported_langs(), [bp_get_default_lang()]))); ?>;

            var work = {};
            targetLangs.forEach(function(lang) { work[lang] = []; });

            var rows = document.querySelectorAll('#bp-translations-form tbody tr');
            rows.forEach(function(row) {
                var sourceEl = row.querySelector('.bp-source-text');
                if (!sourceEl) return;
                var sourceText = (sourceEl.value || sourceEl.textContent || '').trim();
                if (!sourceText) return;

                var inputs = row.querySelectorAll('.bp-translation-input');
                inputs.forEach(function(input) {
                    var lang = input.dataset.lang;
                    var val  = (input.value || input.textContent || '').trim();
                    if (!val && work[lang]) {
                        work[lang].push({ input: input, sourceText: sourceText });
                    }
                });
            });

            var total = 0;
            targetLangs.forEach(function(lang) { total += work[lang].length; });

            if (total === 0) {
                status.textContent = 'All translations are filled!';
                return;
            }

            btn.disabled = true;
            status.textContent = 'Translating ' + total + ' texts...';

            var done   = 0;
            var errors = 0;

            var promises = targetLangs.map(function(lang) {
                var items = work[lang];
                if (items.length === 0) return Promise.resolve();

                var texts    = items.map(function(item) { return item.sourceText; });
                var formData = new FormData();
                formData.append('action', 'bp_translate');
                formData.append('nonce', '<?php echo wp_create_nonce('bp_translate_nonce'); ?>');
                formData.append('source', '<?php echo esc_js(bp_get_default_lang()); ?>');
                formData.append('target', lang);
                texts.forEach(function(t) { formData.append('texts[]', t); });

                return fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success && data.data.translations) {
                        data.data.translations.forEach(function(translated, i) {
                            if (items[i] && items[i].input) {
                                items[i].input.value = translated;
                                items[i].input.style.borderColor = '#00a32a';
                                items[i].input.style.background = '#f0fff0';
                                done++;
                            }
                        });
                    } else {
                        errors += items.length;
                        console.error('Translation error for ' + lang + ':', data);
                    }
                })
                .catch(function(err) {
                    errors += items.length;
                    console.error('Fetch error for ' + lang + ':', err);
                });
            });

            Promise.all(promises).then(function() {
                btn.disabled = false;
                var msg = done + ' translations filled.';
                if (errors > 0) msg += ' ' + errors + ' failed.';
                msg += ' Click "Save Translations" to keep them.';
                status.textContent = msg;
            });
        });
    })();
    </script>
    <?php
}

// ---- Settings Page ---------------------------------------------------------

function bp_translations_settings_render()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    // Handle save
    if (isset($_POST['bp_settings_nonce']) && wp_verify_nonce($_POST['bp_settings_nonce'], 'bp_settings_save')) {
        $api_key = sanitize_text_field($_POST['bp_openai_api_key'] ?? '');
        $model   = sanitize_text_field($_POST['bp_openai_model'] ?? 'gpt-4o-mini');

        update_option('bp_openai_api_key', $api_key, false);
        update_option('bp_openai_model', $model, false);

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'boilerplate') . '</p></div>';
    }

    $api_key   = get_option('bp_openai_api_key', '');
    $model     = get_option('bp_openai_model', 'gpt-4o-mini');
    $has_const = defined('BP_OPENAI_API_KEY') && constant('BP_OPENAI_API_KEY') !== '';
    $config    = bp_get_languages_config();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Translation Settings', 'boilerplate'); ?></h1>

        <form method="post">
            <?php wp_nonce_field('bp_settings_save', 'bp_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('AI Provider', 'boilerplate'); ?></label>
                    </th>
                    <td>
                        <select disabled style="min-width: 200px;">
                            <option selected>OpenAI</option>
                        </select>
                        <p class="description"><?php esc_html_e('More providers can be added later.', 'boilerplate'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bp_openai_api_key"><?php esc_html_e('OpenAI API Key', 'boilerplate'); ?></label>
                    </th>
                    <td>
                        <?php if ($has_const) : ?>
                            <input type="text" value="<?php esc_attr_e('Defined in wp-config.php', 'boilerplate'); ?>" disabled class="regular-text" style="background: #f0f0f0;">
                            <p class="description" style="color: #00a32a;">
                                <?php esc_html_e('API key is set via BP_OPENAI_API_KEY constant in wp-config.php. That takes priority over this field.', 'boilerplate'); ?>
                            </p>
                        <?php else : ?>
                            <input type="password"
                                   id="bp_openai_api_key"
                                   name="bp_openai_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text"
                                   placeholder="sk-..."
                                   autocomplete="off">
                            <button type="button" onclick="var f=document.getElementById('bp_openai_api_key');f.type=f.type==='password'?'text':'password';" class="button" style="vertical-align: top;">
                                <?php esc_html_e('Show/Hide', 'boilerplate'); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e('Get your API key from platform.openai.com. Alternatively, define BP_OPENAI_API_KEY in wp-config.php.', 'boilerplate'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bp_openai_model"><?php esc_html_e('Model', 'boilerplate'); ?></label>
                    </th>
                    <td>
                        <select id="bp_openai_model" name="bp_openai_model" style="min-width: 200px;">
                            <option value="gpt-4o-mini" <?php selected($model, 'gpt-4o-mini'); ?>>gpt-4o-mini (fast, cheap)</option>
                            <option value="gpt-4o" <?php selected($model, 'gpt-4o'); ?>>gpt-4o (best quality)</option>
                            <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo (legacy, cheapest)</option>
                        </select>
                        <p class="description"><?php esc_html_e('gpt-4o-mini is recommended for translations.', 'boilerplate'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Languages', 'boilerplate'); ?></label>
                    </th>
                    <td>
                        <?php foreach (bp_get_supported_langs() as $lang) :
                            $label   = $config[$lang]['label'] ?? strtoupper($lang);
                            $is_default = $lang === bp_get_default_lang();
                        ?>
                            <label style="display: inline-block; margin-right: 16px;">
                                <input type="checkbox" checked disabled>
                                <strong><?php echo esc_html($label); ?></strong><?php if ($is_default) {
                                    echo ' (source)';
                                } ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description" style="margin-top: 8px;">
                            <?php esc_html_e('To add/remove languages, edit inc/translations/config/languages.php.', 'boilerplate'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'boilerplate'); ?></th>
                    <td>
                        <?php
                        $effective_key = $has_const ? constant('BP_OPENAI_API_KEY') : $api_key;
                        if (! empty($effective_key)) :
                        ?>
                            <span style="color: #00a32a; font-weight: 600;">&#10003; <?php esc_html_e('API key configured — AI translation is active.', 'boilerplate'); ?></span>
                        <?php else : ?>
                            <span style="color: #d63638; font-weight: 600;">&#10007; <?php esc_html_e('No API key — AI translation is disabled.', 'boilerplate'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', 'boilerplate')); ?>
        </form>
    </div>
    <?php
}
