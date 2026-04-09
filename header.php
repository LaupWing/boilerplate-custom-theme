<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('antialiased'); ?>>
<?php wp_body_open(); ?>

<?php
$langs   = snel_get_supported_langs();
$current = snel_get_lang();
$config  = snel_get_languages_config();

$lang_full_names = [
    'nl' => 'Nederlands', 'en' => 'English', 'de' => 'Deutsch',
    'fr' => 'Français', 'es' => 'Español', 'it' => 'Italiano',
];
$lang_flags = [
    'nl' => '🇳🇱', 'en' => '🇬🇧', 'de' => '🇩🇪',
    'fr' => '🇫🇷', 'es' => '🇪🇸', 'it' => '🇮🇹',
];

// Get primary menu items.
$menu_locations = get_nav_menu_locations();
$menu_items     = [];
if (! empty($menu_locations['primary'])) {
    $menu_items = wp_get_nav_menu_items($menu_locations['primary']) ?: [];
}
?>

<header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-200">
    <div class="container mx-auto px-6 py-4 flex items-center justify-between">
        <a href="<?php echo esc_url(snel_url('/')); ?>" class="text-xl font-bold text-gray-900">
            <?php bloginfo('name'); ?>
        </a>

        <nav class="flex items-center gap-6">
            <?php foreach ($menu_items as $item) :
                if ((int) $item->menu_item_parent !== 0) continue;
                $url   = snel_url(wp_parse_url($item->url, PHP_URL_PATH) ?: '/');
                $title = snel__($item->title);
            ?>
                <a href="<?php echo esc_url($url); ?>" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <?php echo esc_html($title); ?>
                </a>
            <?php endforeach; ?>

            <?php if (empty($menu_items)) : ?>
                <span class="text-sm text-gray-400">Set up a menu in Appearance > Menus</span>
            <?php endif; ?>

            <!-- Language Switcher -->
            <div class="relative ml-4 border-l border-gray-200 pl-4" id="snel-lang-switcher">
                <button
                    type="button"
                    id="snel-lang-btn"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <span class="text-base leading-none"><?php echo $lang_flags[$current] ?? '🌐'; ?></span>
                    <span><?php echo esc_html($config[$current]['label'] ?? strtoupper($current)); ?></span>
                    <svg class="w-4 h-4 opacity-50 transition-transform" id="snel-lang-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    id="snel-lang-popover"
                    class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black/5 overflow-hidden opacity-0 invisible scale-95 transition-all duration-150 origin-top-right z-50"
                    role="menu"
                >
                    <div class="py-1" id="snel-lang-list">
                        <?php foreach ($langs as $lang) :
                            $url       = snel_lang_url($lang);
                            $label     = $config[$lang]['label'] ?? strtoupper($lang);
                            $full_name = $lang_full_names[$lang] ?? $label;
                            $flag      = $lang_flags[$lang] ?? '🌐';
                            $is_active = ($lang === $current);
                        ?>
                            <a
                                href="<?php echo esc_url($url); ?>"
                                class="flex items-center gap-3 px-3 py-2.5 text-sm transition-colors <?php echo $is_active ? 'bg-gray-50 text-gray-900 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?>"
                                role="menuitem"
                                <?php if ($is_active) echo 'aria-current="true"'; ?>
                            >
                                <span class="text-lg leading-none"><?php echo $flag; ?></span>
                                <span class="flex-1"><?php echo esc_html($full_name); ?></span>
                                <span class="text-xs text-gray-400 font-mono"><?php echo esc_html($label); ?></span>
                                <?php if ($is_active) : ?>
                                    <svg class="w-4 h-4 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="container mx-auto px-6 py-8">
