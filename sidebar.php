<?php
/**
 * The sidebar containing the main widget area.
 *
 * @package Boilerplate
 */

if (! is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside class="w-64 shrink-0">
    <div class="bg-brand-light rounded p-4">
        <?php dynamic_sidebar('sidebar-1'); ?>
    </div>
</aside>
