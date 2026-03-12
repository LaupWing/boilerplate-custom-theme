<?php

/**
 * Page seed data.
 *
 * Each page has:
 *   - title    — Page title (Dutch)
 *   - slug     — WordPress slug (Dutch)
 *   - slugs    — Translated slugs per language (e.g., ['en' => 'about-us'])
 *   - content  — Page content (optional, can include Gutenberg blocks)
 *   - template — Page template file (optional)
 *   - is_front_page — Set as homepage (optional, only one)
 *
 * Edit this file per project.
 *
 * @package Boilerplate
 */

return [
    [
        'title'         => 'Home',
        'slug'          => 'home',
        'slugs'         => [],
        'content'       => '',
        'is_front_page' => true,
    ],
    [
        'title'   => 'Over Ons',
        'slug'    => 'over-ons',
        'slugs'   => ['en' => 'about-us'],
        'content' => '',
    ],
    [
        'title'   => 'Contact',
        'slug'    => 'contact',
        'slugs'   => ['en' => 'contact'],
        'content' => '',
    ],
    [
        'title'   => 'Privacy Policy',
        'slug'    => 'privacy-policy',
        'slugs'   => ['en' => 'privacy-policy'],
        'content' => '',
    ],
];
