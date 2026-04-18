<?php
/**
 * Form handling: inquiry CPT, AJAX submission, email delivery.
 *
 * Drop-in module — auto-loaded via inc/forms/index.php.
 * Enqueues forms.js only on pages that use the contact form.
 *
 * @package Snel
 */

defined( 'ABSPATH' ) || exit;

// ─── Register Inquiry CPT ─────────────────────────────────────────────────────

add_action( 'init', function () {
    register_post_type( 'inquiry', [
        'labels' => [
            'name'          => __( 'Inquiries', 'snel' ),
            'singular_name' => __( 'Inquiry', 'snel' ),
            'menu_name'     => __( 'Inquiries', 'snel' ),
            'all_items'     => __( 'Inquiries', 'snel' ),
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'menu_icon'       => 'dashicons-email-alt',
        'supports'        => [ 'title' ],
        'capability_type' => 'post',
        'capabilities'    => [
            'create_posts' => 'do_not_allow',
        ],
        'map_meta_cap' => true,
    ] );
} );

// ─── Admin columns ────────────────────────────────────────────────────────────

add_filter( 'manage_inquiry_posts_columns', function ( $columns ) {
    return [
        'cb'      => $columns['cb'],
        'title'   => __( 'Name', 'snel' ),
        'email'   => __( 'Email', 'snel' ),
        'subject' => __( 'Subject', 'snel' ),
        'date'    => $columns['date'],
    ];
} );

add_action( 'manage_inquiry_posts_custom_column', function ( $column, $post_id ) {
    switch ( $column ) {
        case 'email':
            echo esc_html( get_post_meta( $post_id, '_inquiry_email', true ) );
            break;
        case 'subject':
            $subject = get_post_meta( $post_id, '_inquiry_subject', true );
            echo esc_html( $subject ?: '—' );
            break;
    }
}, 10, 2 );

// ─── Inquiry detail meta box ─────────────────────────────────────────────────

add_action( 'add_meta_boxes_inquiry', function () {
    add_meta_box(
        'snel_inquiry_details',
        __( 'Inquiry Details', 'snel' ),
        'snel_inquiry_details_meta_box',
        'inquiry',
        'normal',
        'high'
    );
} );

function snel_inquiry_details_meta_box( $post ) {
    $email   = get_post_meta( $post->ID, '_inquiry_email', true );
    $subject = get_post_meta( $post->ID, '_inquiry_subject', true );
    $message = $post->post_content;

    $label_style = 'display:inline-block;width:120px;font-weight:600;color:#1d2327;padding:8px 0;vertical-align:top;';
    $value_style = 'display:inline-block;padding:8px 0;color:#50575e;';
    $row_style   = 'border-bottom:1px solid #f0f0f1;';

    echo '<div style="max-width:600px;">';

    if ( $email ) {
        echo '<div style="' . $row_style . '"><span style="' . $label_style . '">Email</span><span style="' . $value_style . '"><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></span></div>';
    }

    if ( $subject ) {
        echo '<div style="' . $row_style . '"><span style="' . $label_style . '">Subject</span><span style="' . $value_style . '">' . esc_html( $subject ) . '</span></div>';
    }

    if ( $message ) {
        echo '<div style="padding:12px 0;"><span style="display:block;font-weight:600;color:#1d2327;margin-bottom:8px;">Message</span>';
        echo '<div style="background:#f9f9f9;padding:12px 16px;border-radius:4px;white-space:pre-wrap;color:#50575e;line-height:1.6;">' . esc_html( $message ) . '</div>';
        echo '</div>';
    }

    echo '<div style="padding:8px 0;color:#a7aaad;font-size:12px;">Received on ' . esc_html( get_the_date( 'j F Y \a\t H:i', $post ) ) . '</div>';
    echo '</div>';
}

// ─── Enqueue forms JS ─────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_page( 'contact' ) ) return;

    $js_file = get_template_directory() . '/assets/js/forms.js';
    if ( ! file_exists( $js_file ) ) return;

    wp_enqueue_script(
        'snel-forms',
        get_template_directory_uri() . '/assets/js/forms.js',
        [],
        filemtime( $js_file ),
        [ 'strategy' => 'defer', 'in_footer' => true ]
    );
    wp_localize_script( 'snel-forms', 'snelForms', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ] );
} );

// ─── AJAX handler ─────────────────────────────────────────────────────────────

add_action( 'wp_ajax_snel_form_submit',        'snel_handle_form_submit' );
add_action( 'wp_ajax_nopriv_snel_form_submit', 'snel_handle_form_submit' );

function snel_handle_form_submit() {
    // 1. Honeypot — must be empty.
    if ( ! empty( $_POST['snel_website'] ) ) {
        wp_send_json_error( [ 'message' => 'Spam detected.' ], 403 );
    }

    // 2. Nonce verification.
    $form_type = sanitize_text_field( $_POST['snel_form_type'] ?? '' );

    if ( $form_type === 'contact' ) {
        if ( ! wp_verify_nonce( $_POST['snel_contact_nonce'] ?? '', 'snel_contact_form' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
    } else {
        wp_send_json_error( [ 'message' => 'Invalid form.' ], 400 );
    }

    // 3. Rate limiting — max 5 per IP per 10 minutes.
    $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key   = 'snel_form_' . md5( $ip );
    $count = (int) get_transient( $key );

    if ( $count >= 5 ) {
        wp_send_json_error( [ 'message' => 'Too many submissions. Please try again later.' ], 429 );
    }

    set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

    // 4. Sanitize.
    $name          = sanitize_text_field( wp_unslash( $_POST['snel_name']    ?? '' ) );
    $email         = sanitize_email( $_POST['snel_email']                    ?? '' );
    $subject_field = sanitize_text_field( wp_unslash( $_POST['snel_subject'] ?? '' ) );
    $message       = sanitize_textarea_field( wp_unslash( $_POST['snel_message'] ?? '' ) );

    // 5. Validate required fields.
    if ( empty( $name ) || empty( $email ) || ! is_email( $email ) || empty( $message ) ) {
        wp_send_json_error( [ 'message' => 'Please fill in all required fields.' ], 400 );
    }

    // 6. Build and send email.
    $to            = function_exists( 'snel_business' ) ? snel_business( 'email' ) : get_option( 'admin_email' );
    $email_subject = $subject_field ? "Contact: {$subject_field} — {$name}" : "Contact message from {$name}";
    $email_subject = str_replace( [ "\r", "\n" ], '', $email_subject );

    $body_lines = [ "Name: {$name}", "Email: {$email}" ];
    if ( $subject_field ) {
        $body_lines[] = "Subject: {$subject_field}";
    }
    $body_lines[] = '';
    $body_lines[] = 'Message:';
    $body_lines[] = $message;

    $sent = wp_mail( $to, $email_subject, implode( "\n", $body_lines ), [ "Reply-To: {$name} <{$email}>" ] );

    // 7. Store as inquiry CPT.
    $inquiry_id = wp_insert_post( [
        'post_type'    => 'inquiry',
        'post_title'   => $name,
        'post_content' => $message,
        'post_status'  => 'publish',
    ] );

    if ( $inquiry_id && ! is_wp_error( $inquiry_id ) ) {
        update_post_meta( $inquiry_id, '_inquiry_email',   $email );
        update_post_meta( $inquiry_id, '_inquiry_form',    'contact' );
        update_post_meta( $inquiry_id, '_inquiry_subject', $subject_field );
    }

    wp_send_json_success( [ 'message' => "Your message has been sent! We'll get back to you soon." ] );
}
