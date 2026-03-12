<?php

/**
 * Custom Post Type slug translations.
 *
 * Maps the default-language (Dutch) CPT archive slug to translated slugs.
 * These are used to generate URL rewrite rules so that
 * /en/services/ loads the same archive as /diensten/.
 *
 * This covers both the archive and single URLs:
 *   /diensten/           → Dutch archive
 *   /en/services/        → English archive
 *   /diensten/my-post/   → Dutch single
 *   /en/services/my-post → English single
 *
 * Only add CPTs here that have a translated archive slug.
 * CPTs with the same slug in all languages can be skipped.
 *
 * Edit this file per project.
 *
 * @package Boilerplate
 */

return [
    // 'dutch-cpt-slug' => ['en' => 'english-cpt-slug'],

    // 'producten' => ['en' => 'products'],
    // 'diensten'  => ['en' => 'services'],
];
