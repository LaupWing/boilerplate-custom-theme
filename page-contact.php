<?php
/**
 * Contact page — static template with AJAX form submission.
 * Pair with inc/forms/index.php for the handler.
 *
 * @package Snel
 */

get_header();
?>

<main class="mx-auto max-w-2xl px-6 py-16">

    <h1 class="text-3xl font-bold tracking-tight">
        <?php the_title(); ?>
    </h1>

    <form class="snel-contact-form mt-8 flex flex-col gap-5">
        <?php wp_nonce_field( 'snel_contact_form', 'snel_contact_nonce' ); ?>
        <input type="text" name="snel_website" style="display:none;" tabindex="-1" autocomplete="off">

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="contact_name" class="text-sm font-medium"><?php esc_html_e( 'Name', 'snel' ); ?></label>
                <input
                    id="contact_name"
                    name="snel_name"
                    type="text"
                    placeholder="<?php esc_attr_e( 'Your name', 'snel' ); ?>"
                    required
                    class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                >
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="contact_email" class="text-sm font-medium"><?php esc_html_e( 'Email', 'snel' ); ?></label>
                <input
                    id="contact_email"
                    name="snel_email"
                    type="email"
                    placeholder="you@email.com"
                    required
                    class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                >
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="contact_subject" class="text-sm font-medium"><?php esc_html_e( 'Subject', 'snel' ); ?></label>
            <input
                id="contact_subject"
                name="snel_subject"
                type="text"
                placeholder="<?php esc_attr_e( 'What is this about?', 'snel' ); ?>"
                class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            >
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="contact_message" class="text-sm font-medium"><?php esc_html_e( 'Message', 'snel' ); ?></label>
            <textarea
                id="contact_message"
                name="snel_message"
                rows="6"
                placeholder="<?php esc_attr_e( 'Your message…', 'snel' ); ?>"
                required
                class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none"
            ></textarea>
        </div>

        <div>
            <button
                type="submit"
                class="rounded bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-60"
            >
                <?php esc_html_e( 'Send Message', 'snel' ); ?>
            </button>
        </div>
    </form>

</main>

<?php get_footer(); ?>
