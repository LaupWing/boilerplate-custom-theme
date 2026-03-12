<?php

/**
 * Supported languages configuration.
 *
 * The default language has NO URL prefix (e.g., /over-ons/).
 * All other languages get a prefix (e.g., /en/about-us/).
 *
 * Edit this file per project.
 *
 * @package Boilerplate
 */

return [
    'nl' => [
        'label'   => 'NL',
        'locale'  => 'nl_NL',
        'default' => true,
    ],
    'en' => [
        'label'  => 'EN',
        'locale' => 'en_US',
    ],
];
