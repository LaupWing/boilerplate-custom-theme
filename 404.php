<?php
/**
 * 404 template.
 *
 * @package Boilerplate
 */

get_header(); ?>

<div class="text-center py-16">
    <h1 class="text-4xl font-bold mb-4">404</h1>
    <p class="text-text-muted mb-6"><?php echo bp__('Pagina niet gevonden.'); ?></p>
    <a href="<?php echo esc_url(bp_url('/')); ?>" class="text-brand-accent hover:underline">
        <?php echo bp__('Terug naar home'); ?>
    </a>
</div>

<?php get_footer();
