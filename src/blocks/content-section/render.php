<?php
/**
 * Content Section — server-side render.
 *
 * Default language content comes from InnerBlocks ($content).
 * Other languages are stored in contentTranslations attribute.
 *
 * @package Boilerplate
 */

$bg_mode = $attributes['bgMode'] ?? 'white';
$bg_classes = [
    'white' => 'bg-white',
    'light' => 'bg-brand-light',
    'dark'  => 'bg-brand-dark text-text-light',
];
$section_bg = $bg_classes[$bg_mode] ?? 'bg-white';

$lang    = bp_get_lang();
$default = bp_get_default_lang();
$translations = $attributes['contentTranslations'] ?? [];

// Default language: render inner blocks. Other languages: render translated HTML.
if ($lang !== $default && ! empty($translations[$lang])) {
    $body = wp_kses_post($translations[$lang]);
} else {
    $body = $content;
}

if (empty(trim(strip_tags($body)))) {
    return;
}
?>

<section <?php echo get_block_wrapper_attributes(['class' => "$section_bg py-16"]); ?>>
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="prose max-w-none">
            <?php echo $body; ?>
        </div>
    </div>
</section>
