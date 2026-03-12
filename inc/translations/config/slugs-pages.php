<?php

/**
 * Page slug translations.
 *
 * Maps the default-language (Dutch) page slug to translated slugs.
 * These are used to generate URL rewrite rules so that
 * /en/about-us/ loads the same page as /over-ons/.
 *
 * Only add pages here that need a translated URL.
 * Pages with the same slug in all languages (e.g., "contact") can be skipped.
 *
 * Edit this file per project.
 *
 * @package Boilerplate
 */

return [
    // 'dutch-slug' => ['en' => 'english-slug'],

    'over-ons' => ['en' => 'about-us'],
    // 'contact' => ['en' => 'contact'],  // same slug, no need to add
];
