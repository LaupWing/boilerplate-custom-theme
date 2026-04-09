<?php
/**
 * Snelstack Settings — Entry Point.
 *
 * Provides shared configuration for all Snelstack plugins.
 * API keys and AI model settings live here.
 *
 * @package AntiqueWarehouse
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Prevent WordPress from overriding SVG icon colors on Snel menu items.
 */
add_action( 'admin_head', function () {
    echo '<style>
        @keyframes snel-gradient-spin {
            0%   { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .snel-menu-icon {
            display: inline-block;
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #3b82f6, #7c3aed);
            border-radius: 50%;
            position: relative;
            vertical-align: middle;
            overflow: hidden;
        }
        .snel-menu-icon svg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 14px;
            height: 14px;
            z-index: 2;
        }
        .snel-menu-icon.is-active {
            background: none;
        }
        .snel-gradient-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 32px;
            height: 32px;
            background: conic-gradient(from 0deg, #06b6d4, #3b82f6, #8b5cf6, #d946ef, #f43f5e, #f97316, #eab308, #22c55e, #06b6d4);
            animation: snel-gradient-spin 3s linear infinite;
            z-index: 1;
        }
        #adminmenu .toplevel_page_snel-seo .wp-menu-image,
        #adminmenu .toplevel_page_snel-translations .wp-menu-image,
        #adminmenu .toplevel_page_snel-newsletter .wp-menu-image,
        #adminmenu .toplevel_page_snelstack .wp-menu-image {
            display: flex !important;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: none !important;
        }
        #adminmenu .toplevel_page_snel-seo,
        #adminmenu .toplevel_page_snel-translations,
        #adminmenu .toplevel_page_snel-newsletter,
        #adminmenu .toplevel_page_snelstack {
            position: relative;
            z-index: 1;
        }
        #adminmenu .toplevel_page_snel-seo .wp-menu-image br,
        #adminmenu .toplevel_page_snel-translations .wp-menu-image br,
        #adminmenu .toplevel_page_snel-newsletter .wp-menu-image br,
        #adminmenu .toplevel_page_snelstack .wp-menu-image br {
            display: none;
        }
    </style>';
} );

/**
 * Replace SVG icon markup with custom branded icon for all Snel menu items.
 */
add_action( 'admin_footer', function () {
    echo '<script>
        var snelIcons = {
            "snel-seo": \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>\',
            "snel-translations": \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2v3"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>\',
            "snel-newsletter": \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>\',
            "snelstack": \'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" fill="#fff"/></svg>\'
        };
        document.querySelectorAll(
            "#adminmenu .toplevel_page_snel-seo .wp-menu-image," +
            "#adminmenu .toplevel_page_snel-translations .wp-menu-image," +
            "#adminmenu .toplevel_page_snel-newsletter .wp-menu-image," +
            "#adminmenu .toplevel_page_snelstack .wp-menu-image"
        ).forEach(function(el) {
            var li = el.closest("li");
            var isActive = li && (li.classList.contains("wp-has-current-submenu") || li.classList.contains("current"));
            var ring = isActive ? \'<span class="snel-gradient-ring"></span>\' : "";
            var activeClass = isActive ? " is-active" : "";
            var slug = li.className.match(/toplevel_page_([\w-]+)/);
            var svg = slug ? (snelIcons[slug[1]] || snelIcons["snelstack"]) : snelIcons["snelstack"];
            el.innerHTML = \'<span class="snel-menu-icon\' + activeClass + \'">\' + ring + svg + \'</span>\';
        });
    </script>';
} );

/**
 * Register the Snelstack settings page.
 */
add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'Snel Stack', 'snel' ),
        __( 'Snel Stack', 'snel' ),
        'manage_options',
        'snelstack',
        'snelstack_render_settings',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0Ij48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwIiB5MT0iMCIgeDI9IjEiIHkyPSIxIj48c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjM2I4MmY2Ii8+PHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjN2MzYWVkIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9InVybCgjZykiLz48cGF0aCBkPSJNNi41IDEzYS43LjcgMCAwIDEtLjU1LTEuMTRsNi45My03LjE0YS4zNS4zNSAwIDAgMSAuNi4zMkwxMi4xNCA5LjJhLjcuNyAwIDAgMCAuNjYuOTVoNC45YS43LjcgMCAwIDEgLjU1IDEuMTRsLTYuOTMgNy4xNGEuMzUuMzUgMCAwIDEtLjYtLjMybDEuMzQtNC4yMUEuNy43IDAgMCAwIDExLjQgMTN6IiBmaWxsPSIjZmZmIi8+PC9zdmc+',
        29
    );
} );

/**
 * Register settings.
 */
add_action( 'admin_init', function () {
    register_setting( 'snelstack_settings', 'snelstack_openai_key', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'snelstack_settings', 'snelstack_openai_model', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'gpt-4o-mini',
    ) );
} );

// snelstack_get_openai_key() is defined in functions.php (outside is_admin)
// so REST endpoints can access it. No duplicate declaration here.

/**
 * Get the OpenAI model from Snelstack settings.
 */
if ( ! function_exists( 'snelstack_get_openai_model' ) ) {
    function snelstack_get_openai_model() {
        $model = get_option( 'snelstack_openai_model', '' );
        if ( $model ) return $model;
        return get_option( 'snel_openai_model', 'gpt-4o-mini' );
    }
}

/**
 * Render the settings page.
 */
function snelstack_render_settings() {
    $api_key = get_option( 'snelstack_openai_key', '' );
    $model   = get_option( 'snelstack_openai_model', '' ) ?: get_option( 'snel_openai_model', 'gpt-4o-mini' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Snelstack Settings', 'snel' ); ?></h1>
        <p style="color: #666; margin-bottom: 24px;">
            <?php esc_html_e( 'Shared configuration for all Snelstack plugins (Snel SEO, Translations, etc.).', 'snel' ); ?>
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'snelstack_settings' ); ?>

            <table class="form-table">
                <!-- API Key -->
                <tr>
                    <th scope="row">
                        <label for="snelstack_openai_key">
                            <?php esc_html_e( 'OpenAI API Key', 'snel' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="password"
                            id="snelstack_openai_key"
                            name="snelstack_openai_key"
                            value="<?php echo esc_attr( $api_key ); ?>"
                            class="regular-text"
                            placeholder="sk-..."
                            autocomplete="off"
                        />
                        <button type="button" onclick="var f=document.getElementById('snelstack_openai_key');f.type=f.type==='password'?'text':'password';" class="button" style="vertical-align: top;">
                            <?php esc_html_e( 'Show/Hide', 'snel' ); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e( 'Used by Snel SEO (AI generation) and the translation system.', 'snel' ); ?>
                            <a href="https://platform.openai.com/api-keys" target="_blank"><?php esc_html_e( 'Get your key', 'snel' ); ?></a>
                        </p>
                        <?php if ( snelstack_get_openai_key() ) : ?>
                            <p style="color: #059669; margin-top: 4px; font-weight: 600;">
                                &#10003; <?php esc_html_e( 'API key is configured — AI features are active.', 'snel' ); ?>
                            </p>
                        <?php else : ?>
                            <p style="color: #d63638; margin-top: 4px; font-weight: 600;">
                                &#10007; <?php esc_html_e( 'No API key — AI features are disabled.', 'snel' ); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Model -->
                <tr>
                    <th scope="row">
                        <label for="snelstack_openai_model">
                            <?php esc_html_e( 'AI Model', 'snel' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="snelstack_openai_model" name="snelstack_openai_model" style="min-width: 200px;">
                            <option value="gpt-4o-mini" <?php selected( $model, 'gpt-4o-mini' ); ?>>gpt-4o-mini (fast, cheap)</option>
                            <option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>>gpt-4o (best quality)</option>
                            <option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>gpt-3.5-turbo (legacy, cheapest)</option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Used for both SEO generation and translations. gpt-4o-mini is recommended.', 'snel' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
