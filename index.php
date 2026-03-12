<?php
/**
 * The main template file.
 *
 * @package Boilerplate
 */

get_header(); ?>

<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <article class="mb-8">
            <h2 class="text-2xl font-bold mb-2">
                <a href="<?php the_permalink(); ?>" class="text-text-primary hover:text-brand-accent">
                    <?php the_title(); ?>
                </a>
            </h2>
            <div class="text-sm text-text-muted mb-4">
                <?php echo get_the_date(); ?>
            </div>
            <div class="prose">
                <?php the_excerpt(); ?>
            </div>
        </article>
    <?php endwhile; ?>

    <div class="mt-8">
        <?php the_posts_pagination(); ?>
    </div>
<?php else : ?>
    <p class="text-text-muted">No posts found.</p>
<?php endif; ?>

<?php get_footer();
