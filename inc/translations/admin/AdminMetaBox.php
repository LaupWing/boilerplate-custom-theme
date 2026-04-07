<?php

/**
 * SEO & Translations meta box on the post/page editor.
 *
 * Adds a meta box where admins can enter per-language:
 * - Title tag (for SEO)
 * - Meta description (for SEO)
 *
 * Data is stored in post meta:
 *   _title_{lang}     — translated document title
 *   _meta_desc_{lang} — translated meta description
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

class AdminMetaBox
{
    /**
     * Register WordPress hooks for the meta box.
     */
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetaBox']);
        add_action('save_post', [self::class, 'save']);
    }

    /**
     * Register the meta box on all public post types.
     */
    public static function addMetaBox(): void
    {
        $post_types = array_merge(
            ['page'],
            array_keys(get_post_types(['_builtin' => false, 'public' => true]))
        );

        foreach ($post_types as $pt) {
            add_meta_box(
                'snel_seo_translations',
                'SEO & Translations',
                [self::class, 'render'],
                $pt,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the meta box HTML.
     *
     * Shows a table with one row per non-default language,
     * columns for slug, title tag, and meta description.
     *
     * @param WP_Post $post The current post.
     */
    public static function render($post): void
    {
        $langs   = LocaleManager::supported();
        $default = LocaleManager::default();
        $config  = LocaleManager::config();

        wp_nonce_field('snel_save_seo_meta', 'snel_seo_nonce');

        echo '<p class="description">Leave fields empty to use the default language values.</p>';

        echo '<table class="widefat" style="border:0;box-shadow:none;">';
        echo '<thead><tr>';
        echo '<th style="width:50px;">Lang</th>';
        echo '<th>Title Tag</th>';
        echo '<th>Meta Description</th>';
        echo '</tr></thead><tbody>';

        foreach ($langs as $lang) {
            if ($lang === $default) {
                continue;
            }

            $label     = $config[$lang]['label'] ?? strtoupper($lang);
            $title_val = get_post_meta($post->ID, '_title_' . $lang, true);
            $desc_val  = get_post_meta($post->ID, '_meta_desc_' . $lang, true);

            echo '<tr>';
            echo '<td><strong>' . esc_html($label) . '</strong></td>';

            echo '<td>';
            echo '<input type="text" name="snel_title_' . esc_attr($lang) . '" ';
            echo 'value="' . esc_attr($title_val) . '" ';
            echo 'placeholder="' . esc_attr(get_the_title($post)) . '" ';
            echo 'style="width:100%;" maxlength="60" />';
            echo '</td>';

            echo '<td>';
            echo '<textarea name="snel_meta_desc_' . esc_attr($lang) . '" ';
            echo 'placeholder="Meta description..." ';
            echo 'style="width:100%;height:40px;resize:vertical;" maxlength="155">';
            echo esc_textarea($desc_val);
            echo '</textarea>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Save the SEO & translation meta when the post is saved.
     *
     * @param int $post_id The post ID.
     */
    public static function save(int $post_id): void
    {
        if (! isset($_POST['snel_seo_nonce']) || ! wp_verify_nonce($_POST['snel_seo_nonce'], 'snel_save_seo_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $langs   = LocaleManager::supported();
        $default = LocaleManager::default();

        foreach ($langs as $lang) {
            if ($lang === $default) {
                continue;
            }

            $title_key = 'snel_title_' . $lang;
            if (isset($_POST[$title_key])) {
                $title = sanitize_text_field(wp_unslash($_POST[$title_key]));
                if ($title) {
                    update_post_meta($post_id, '_title_' . $lang, $title);
                } else {
                    delete_post_meta($post_id, '_title_' . $lang);
                }
            }

            $desc_key = 'snel_meta_desc_' . $lang;
            if (isset($_POST[$desc_key])) {
                $desc = sanitize_text_field(wp_unslash($_POST[$desc_key]));
                if ($desc) {
                    update_post_meta($post_id, '_meta_desc_' . $lang, $desc);
                } else {
                    delete_post_meta($post_id, '_meta_desc_' . $lang);
                }
            }
        }
    }
}
