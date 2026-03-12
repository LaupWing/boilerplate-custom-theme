<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('antialiased'); ?>>
<?php wp_body_open(); ?>

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-6 py-4 flex items-center justify-between">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-text-primary">
            <?php bloginfo('name'); ?>
        </a>

        <?php if (has_nav_menu('primary')) : ?>
            <nav>
                <?php wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'flex gap-6 text-sm text-text-secondary',
                )); ?>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main class="container mx-auto px-6 py-8">
