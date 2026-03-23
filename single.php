<?php
/**
 * Single post template.
 *
 * @package Snel
 */

get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
    <article>
        <h1 class="text-3xl font-bold mb-2"><?php the_title(); ?></h1>
        <div class="text-sm text-text-muted mb-6"><?php echo get_the_date(); ?></div>
        <div class="prose max-w-none">
            <?php the_content(); ?>
        </div>
    </article>
<?php endwhile; ?>

<?php get_footer();
