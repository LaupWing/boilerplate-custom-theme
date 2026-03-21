<?php
/**
 * Snelstack Settings — Entry Point.
 *
 * Provides shared configuration for all Snelstack plugins.
 * API keys and AI model settings live here.
 *
 * @package Boilerplate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Snelstack settings page.
 */
add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'Snelstack', 'boilerplate' ),
        __( 'Snelstack', 'boilerplate' ),
        'manage_options',
        'snelstack',
        'snelstack_render_settings',
        'dashicons-admin-generic',
        81
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

/**
 * Get the OpenAI API key from the unified Snelstack settings.
 * Falls back to legacy constants/options for backwards compatibility.
 */
function snelstack_get_openai_key() {
    $key = get_option( 'snelstack_openai_key', '' );
    if ( $key ) return $key;

    if ( defined( 'SNEL_SEO_OPENAI_KEY' ) && constant( 'SNEL_SEO_OPENAI_KEY' ) ) {
        return constant( 'SNEL_SEO_OPENAI_KEY' );
    }
    if ( defined( 'BP_OPENAI_API_KEY' ) && constant( 'BP_OPENAI_API_KEY' ) ) {
        return constant( 'BP_OPENAI_API_KEY' );
    }

    $legacy = get_option( 'bp_openai_api_key', '' );
    if ( $legacy ) return $legacy;

    return '';
}

/**
 * Get the OpenAI model from Snelstack settings.
 */
function snelstack_get_openai_model() {
    $model = get_option( 'snelstack_openai_model', '' );
    if ( $model ) return $model;

    return get_option( 'bp_openai_model', 'gpt-4o-mini' );
}

/**
 * Render the settings page.
 */
function snelstack_render_settings() {
    $api_key = get_option( 'snelstack_openai_key', '' );
    $model   = get_option( 'snelstack_openai_model', '' ) ?: get_option( 'bp_openai_model', 'gpt-4o-mini' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Snelstack Settings', 'boilerplate' ); ?></h1>
        <p style="color: #666; margin-bottom: 24px;">
            <?php esc_html_e( 'Shared configuration for all Snelstack plugins (Snel SEO, Translations, etc.).', 'boilerplate' ); ?>
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'snelstack_settings' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snelstack_openai_key">
                            <?php esc_html_e( 'OpenAI API Key', 'boilerplate' ); ?>
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
                            <?php esc_html_e( 'Show/Hide', 'boilerplate' ); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e( 'Used by Snel SEO (AI generation) and the translation system.', 'boilerplate' ); ?>
                            <a href="https://platform.openai.com/api-keys" target="_blank"><?php esc_html_e( 'Get your key', 'boilerplate' ); ?></a>
                        </p>
                        <?php if ( snelstack_get_openai_key() ) : ?>
                            <p style="color: #059669; margin-top: 4px; font-weight: 600;">
                                &#10003; <?php esc_html_e( 'API key is configured — AI features are active.', 'boilerplate' ); ?>
                            </p>
                        <?php else : ?>
                            <p style="color: #d63638; margin-top: 4px; font-weight: 600;">
                                &#10007; <?php esc_html_e( 'No API key — AI features are disabled.', 'boilerplate' ); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="snelstack_openai_model">
                            <?php esc_html_e( 'AI Model', 'boilerplate' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="snelstack_openai_model" name="snelstack_openai_model" style="min-width: 200px;">
                            <option value="gpt-4o-mini" <?php selected( $model, 'gpt-4o-mini' ); ?>>gpt-4o-mini (fast, cheap)</option>
                            <option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>>gpt-4o (best quality)</option>
                            <option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>gpt-3.5-turbo (legacy, cheapest)</option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Used for both SEO generation and translations. gpt-4o-mini is recommended.', 'boilerplate' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
