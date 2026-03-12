<?php
/**
 * Archive template.
 *
 * @package Boilerplate
 */

get_header(); ?>

<h1 class="text-3xl font-bold mb-6"><?php the_archive_title(); ?></h1>

<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <article class="mb-8">
            <h2 class="text-xl font-bold mb-1">
                <a href="<?php the_permalink(); ?>" class="text-text-primary hover:text-brand-accent">
                    <?php the_title(); ?>
                </a>
            </h2>
            <div class="text-sm text-text-muted mb-2"><?php echo get_the_date(); ?></div>
            <div class="prose"><?php the_excerpt(); ?></div>
        </article>
    <?php endwhile; ?>

    <?php the_posts_pagination(); ?>
<?php else : ?>
    <p class="text-text-muted"><?php echo bp__('Geen resultaten gevonden.'); ?></p>
<?php endif; ?>

<?php get_footer();
