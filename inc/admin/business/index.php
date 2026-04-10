<?php
/**
 * Business Settings
 *
 * Centralized business/brand info stored in wp_options.
 * Admin page + snel_business() helper for use in templates.
 *
 * Portable: copy to any theme, update defaults.
 */

defined('ABSPATH') || exit;

// ─── Defaults ───────────────────────────────────────────────────────────────

function snel_business_defaults() {
    return [
        'name'           => '',
        'logo_id'        => '',
        'logo_inverted_id' => '',
        'email'          => '',
        'instagram_url'  => '',
        'x_url'          => '',
        'youtube_url'    => '',
        'tiktok_url'     => '',
        'linkedin_url'   => '',
    ];
}

// ─── Helper ─────────────────────────────────────────────────────────────────

/**
 * Get a business setting value.
 *
 * Usage: snel_business('email')     → 'loc@example.com'
 *        snel_business('logo_url')  → full URL to logo image
 *        snel_business('logo_inverted_url') → full URL to inverted logo
 *
 * @param string $key The setting key.
 * @return string The value, or empty string if not found.
 */
function snel_business($key) {
    static $settings = null;
    if ($settings === null) {
        $defaults = snel_business_defaults();
        $saved    = get_option('snel_business_settings', []);
        $settings = wp_parse_args($saved, $defaults);
    }

    // Special key: logo_url returns the image URL from the attachment ID.
    if ($key === 'logo_url') {
        $logo_id = $settings['logo_id'] ?? '';
        if ($logo_id) {
            $url = wp_get_attachment_image_url(absint($logo_id), 'medium');
            if ($url) return $url;
        }
        $local = get_template_directory() . '/assets/images/logo-ln-original.png';
        if (file_exists($local)) {
            return get_template_directory_uri() . '/assets/images/logo-ln-original.png';
        }
        return '';
    }

    // Special key: logo_inverted_url for dark backgrounds.
    if ($key === 'logo_inverted_url') {
        $logo_id = $settings['logo_inverted_id'] ?? '';
        if ($logo_id) {
            $url = wp_get_attachment_image_url(absint($logo_id), 'medium');
            if ($url) return $url;
        }
        $local = get_template_directory() . '/assets/images/logo-ln-inverted.png';
        if (file_exists($local)) {
            return get_template_directory_uri() . '/assets/images/logo-ln-inverted.png';
        }
        return '';
    }

    return $settings[$key] ?? '';
}

// ─── Admin Page ─────────────────────────────────────────────────────────────

if (!is_admin()) return;

add_action('admin_menu', function () {
    add_menu_page(
        __('Business Info', 'snel'),
        __('Business Info', 'snel'),
        'manage_options',
        'snel-business-settings',
        'snel_business_settings_render',
        'dashicons-id',
        71
    );
});

function snel_business_settings_render() {
    if (!current_user_can('manage_options')) return;

    $defaults = snel_business_defaults();
    $saved    = get_option('snel_business_settings', []);
    $settings = wp_parse_args($saved, $defaults);
    $success  = false;

    // Handle save.
    if (isset($_POST['snel_business_nonce']) && wp_verify_nonce($_POST['snel_business_nonce'], 'snel_business_save')) {
        $new = [];
        foreach (array_keys($defaults) as $key) {
            $new[$key] = sanitize_text_field(wp_unslash($_POST['snel_business'][$key] ?? ''));
        }
        update_option('snel_business_settings', $new, false);
        $settings = $new;
        $success  = true;
    }

    // Enqueue media uploader.
    wp_enqueue_media();

    // Logo URLs for preview.
    $logo_url = '';
    if (!empty($settings['logo_id'])) {
        $logo_url = wp_get_attachment_image_url(absint($settings['logo_id']), 'medium');
    }
    if (!$logo_url) {
        $local = get_template_directory() . '/assets/images/logo-ln-original.png';
        if (file_exists($local)) {
            $logo_url = get_template_directory_uri() . '/assets/images/logo-ln-original.png';
        }
    }

    $logo_inverted_url = '';
    if (!empty($settings['logo_inverted_id'])) {
        $logo_inverted_url = wp_get_attachment_image_url(absint($settings['logo_inverted_id']), 'medium');
    }
    if (!$logo_inverted_url) {
        $local = get_template_directory() . '/assets/images/logo-ln-inverted.png';
        if (file_exists($local)) {
            $logo_inverted_url = get_template_directory_uri() . '/assets/images/logo-ln-inverted.png';
        }
    }

    $fields = [
        'General' => [
            'name'  => ['label' => __('Brand name', 'snel'), 'placeholder' => 'Loc Nguyen'],
            'email' => ['label' => __('Email address', 'snel'), 'placeholder' => 'hello@example.com'],
        ],
        'Social Media' => [
            'instagram_url' => ['label' => 'Instagram URL', 'placeholder' => 'https://www.instagram.com/...'],
            'x_url'         => ['label' => 'X (Twitter) URL', 'placeholder' => 'https://x.com/...'],
            'youtube_url'   => ['label' => 'YouTube URL', 'placeholder' => 'https://www.youtube.com/...'],
            'tiktok_url'    => ['label' => 'TikTok URL', 'placeholder' => 'https://www.tiktok.com/@...'],
            'linkedin_url'  => ['label' => 'LinkedIn URL', 'placeholder' => 'https://www.linkedin.com/in/...'],
        ],
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Business Info', 'snel'); ?></h1>
        <p class="description"><?php esc_html_e('Brand info used throughout the site (header, footer, social links). Update it here and it applies everywhere.', 'snel'); ?></p>

        <?php if ($success) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Business info saved.', 'snel'); ?></p></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('snel_business_save', 'snel_business_nonce'); ?>

            <!-- Logo -->
            <h2 style="margin-top: 24px;"><?php esc_html_e('Logo (light background)', 'snel'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e('Logo', 'snel'); ?></label></th>
                    <td>
                        <div id="snel-logo-preview" style="margin-bottom: 10px;">
                            <?php if ($logo_url) : ?>
                                <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; max-height: 80px; display: block;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="snel_business_logo_id" name="snel_business[logo_id]" value="<?php echo esc_attr($settings['logo_id']); ?>">
                        <button type="button" id="snel-logo-upload" class="button"><?php esc_html_e('Select Logo', 'snel'); ?></button>
                        <button type="button" id="snel-logo-remove" class="button" <?php echo empty($settings['logo_id']) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Remove', 'snel'); ?></button>
                        <p class="description"><?php esc_html_e('Used in the header. Falls back to assets/images/logo-ln-original.png.', 'snel'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Inverted Logo -->
            <h2 style="margin-top: 24px;"><?php esc_html_e('Logo (dark background)', 'snel'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e('Inverted logo', 'snel'); ?></label></th>
                    <td>
                        <div id="snel-logo-inv-preview" style="margin-bottom: 10px; background: #1a1a1a; padding: 12px; border-radius: 6px; display: inline-block;">
                            <?php if ($logo_inverted_url) : ?>
                                <img src="<?php echo esc_url($logo_inverted_url); ?>" style="max-width: 200px; max-height: 80px; display: block;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="snel_business_logo_inverted_id" name="snel_business[logo_inverted_id]" value="<?php echo esc_attr($settings['logo_inverted_id']); ?>">
                        <button type="button" id="snel-logo-inv-upload" class="button"><?php esc_html_e('Select Logo', 'snel'); ?></button>
                        <button type="button" id="snel-logo-inv-remove" class="button" <?php echo empty($settings['logo_inverted_id']) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Remove', 'snel'); ?></button>
                        <p class="description"><?php esc_html_e('Used in the footer / dark sections. Falls back to assets/images/logo-ln-inverted.png.', 'snel'); ?></p>
                    </td>
                </tr>
            </table>

            <?php foreach ($fields as $section => $section_fields) : ?>
                <h2 style="margin-top: 24px;"><?php echo esc_html($section); ?></h2>
                <table class="form-table">
                    <?php foreach ($section_fields as $key => $field) : ?>
                        <tr>
                            <th scope="row"><label for="snel_business_<?php echo esc_attr($key); ?>"><?php echo esc_html($field['label']); ?></label></th>
                            <td>
                                <input type="text"
                                       id="snel_business_<?php echo esc_attr($key); ?>"
                                       name="snel_business[<?php echo esc_attr($key); ?>]"
                                       value="<?php echo esc_attr($settings[$key]); ?>"
                                       class="regular-text"
                                       placeholder="<?php echo esc_attr($field['placeholder']); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>

            <?php submit_button(__('Save Business Info', 'snel')); ?>
        </form>
    </div>

    <script>
    jQuery(function($) {
        function logoUploader(btnId, removeId, inputId, previewId, darkBg) {
            var frame;
            $(btnId).on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Select Logo', multiple: false, library: { type: 'image' } });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $(inputId).val(attachment.id);
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    var img = '<img src="' + url + '" style="max-width:200px;max-height:80px;display:block;">';
                    if (darkBg) {
                        $(previewId).html(img).css({ background: '#1a1a1a', padding: '12px', borderRadius: '6px', display: 'inline-block' });
                    } else {
                        $(previewId).html(img);
                    }
                    $(removeId).show();
                });
                frame.open();
            });
            $(removeId).on('click', function(e) {
                e.preventDefault();
                $(inputId).val('');
                $(previewId).html('');
                $(this).hide();
            });
        }

        logoUploader('#snel-logo-upload', '#snel-logo-remove', '#snel_business_logo_id', '#snel-logo-preview', false);
        logoUploader('#snel-logo-inv-upload', '#snel-logo-inv-remove', '#snel_business_logo_inverted_id', '#snel-logo-inv-preview', true);
    });
    </script>
    <?php
}
