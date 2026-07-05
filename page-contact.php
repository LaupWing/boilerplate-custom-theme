<?php
/**
 * Contact page — styled form that POSTs directly to an n8n webhook.
 * No plugins. Set your webhook URL in the data-webhook attribute below.
 *
 * @package Snel
 */

get_header();
?>

<main class="mx-auto max-w-xl px-6 py-16 sm:py-24">

    <h1 class="text-3xl font-bold tracking-tight text-brand-primary">
        <?php the_title(); ?>
    </h1>
    <p class="mt-3 text-text-secondary">
        <?php esc_html_e( 'Fill in the form and we will get back to you.', 'snel' ); ?>
    </p>

    <form
        class="snel-contact-form mt-8 flex flex-col gap-5"
        data-webhook="https://n8n.snelstack.com/webhook/bcddc1d4-348d-40da-988d-9d67acfd7ee9"
    >
        <div class="flex flex-col gap-1.5">
            <label for="contact_name" class="text-sm font-medium text-brand-primary"><?php esc_html_e( 'Name', 'snel' ); ?></label>
            <input id="contact_name" name="name" type="text" placeholder="<?php esc_attr_e( 'Your name', 'snel' ); ?>" required
                class="rounded-lg border border-border-light bg-white px-4 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/40">
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="contact_email" class="text-sm font-medium text-brand-primary"><?php esc_html_e( 'Email', 'snel' ); ?></label>
            <input id="contact_email" name="email" type="email" placeholder="you@email.com" required
                class="rounded-lg border border-border-light bg-white px-4 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/40">
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="contact_phone" class="text-sm font-medium text-brand-primary"><?php esc_html_e( 'Phone', 'snel' ); ?></label>
            <input id="contact_phone" name="phone" type="tel" placeholder="+31 6 12345678"
                class="rounded-lg border border-border-light bg-white px-4 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/40">
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="contact_message" class="text-sm font-medium text-brand-primary"><?php esc_html_e( 'Message', 'snel' ); ?></label>
            <textarea id="contact_message" name="message" rows="6" placeholder="<?php esc_attr_e( 'Your message…', 'snel' ); ?>" required
                class="resize-none rounded-lg border border-border-light bg-white px-4 py-2.5 text-sm text-text-primary placeholder:text-text-muted focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/40"></textarea>
        </div>

        <div>
            <button type="submit"
                class="rounded-lg bg-brand-accent px-6 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60">
                <?php esc_html_e( 'Send Message', 'snel' ); ?>
            </button>
        </div>

        <p class="snel-contact-status hidden text-sm"></p>
    </form>

</main>

<script>
( function () {
    const form = document.querySelector( '.snel-contact-form' );
    if ( ! form ) return;
    const status = form.querySelector( '.snel-contact-status' );
    const button = form.querySelector( 'button[type="submit"]' );

    form.addEventListener( 'submit', async function ( e ) {
        e.preventDefault();
        button.disabled = true;
        status.className = 'snel-contact-status text-sm text-text-muted';
        status.textContent = 'Sending…';

        const data = Object.fromEntries( new FormData( form ).entries() );

        try {
            const res = await fetch( form.dataset.webhook, {
                method: 'POST',
                body: new URLSearchParams( data ),
            } );
            if ( ! res.ok ) throw new Error( res.status );
            form.reset();
            status.className = 'snel-contact-status text-sm text-brand-accent';
            status.textContent = 'Thanks! Your message was sent.';
        } catch ( err ) {
            status.className = 'snel-contact-status text-sm text-red-600';
            status.textContent = 'Something went wrong. Please try again.';
        } finally {
            button.disabled = false;
        }
    } );
} )();
</script>

<?php get_footer(); ?>
