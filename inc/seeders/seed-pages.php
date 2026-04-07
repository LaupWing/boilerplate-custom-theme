<?php

/**
 * Page seed data.
 *
 * Each page has:
 *   - title    — Page title (Dutch, the default language)
 *   - slug     — WordPress slug (Dutch)
 *   - slugs    — Translated slugs per language
 *   - content  — Page content (Gutenberg blocks)
 *   - template — Page template file (optional)
 *   - is_front_page — Set as homepage (optional, only one)
 *
 * Edit this file per project.
 *
 * @package Snel
 */

// Content Section block helper
function snel_seed_content_section($nl_html, $translations = [], $bg = 'white')
{
    $attrs = [
        'bgMode'              => $bg,
        'contentTranslations' => $translations,
    ];

    $json = wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return '<!-- wp:snel/content-section ' . $json . ' -->'
         . $nl_html
         . '<!-- /wp:snel/content-section -->';
}

return [
    [
        'title'         => 'Home',
        'slug'          => 'home',
        'slugs'         => [],
        'is_front_page' => true,
        'content'       => snel_seed_content_section(
            '<!-- wp:heading --><h2>Welkom bij ons bedrijf</h2><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>Wij zijn gespecialiseerd in het leveren van hoogwaardige oplossingen voor onze klanten. Met jarenlange ervaring en een passie voor kwaliteit helpen wij u graag verder.</p><!-- /wp:paragraph -->'
            . '<!-- wp:paragraph --><p>Neem gerust contact met ons op om te ontdekken wat wij voor u kunnen betekenen.</p><!-- /wp:paragraph -->',
            [
                'en' => ['<h2>Welcome to our company</h2>', '<p>We specialize in delivering high-quality solutions for our clients. With years of experience and a passion for quality, we are happy to help you.</p>', '<p>Feel free to contact us to discover what we can do for you.</p>'],
                'de' => ['<h2>Willkommen bei unserem Unternehmen</h2>', '<p>Wir sind spezialisiert auf die Bereitstellung hochwertiger Lösungen für unsere Kunden. Mit jahrelanger Erfahrung und einer Leidenschaft für Qualität helfen wir Ihnen gerne weiter.</p>', '<p>Kontaktieren Sie uns gerne, um zu erfahren, was wir für Sie tun können.</p>'],
                'fr' => ['<h2>Bienvenue dans notre entreprise</h2>', '<p>Nous sommes spécialisés dans la fourniture de solutions de haute qualité pour nos clients. Avec des années d\'expérience et une passion pour la qualité, nous sommes heureux de vous aider.</p>', '<p>N\'hésitez pas à nous contacter pour découvrir ce que nous pouvons faire pour vous.</p>'],
                'es' => ['<h2>Bienvenido a nuestra empresa</h2>', '<p>Nos especializamos en ofrecer soluciones de alta calidad para nuestros clientes. Con años de experiencia y pasión por la calidad, estamos encantados de ayudarle.</p>', '<p>No dude en contactarnos para descubrir lo que podemos hacer por usted.</p>'],
            ]
        ),
    ],
    [
        'title'   => 'Over Ons',
        'slug'    => 'over-ons',
        'slugs'   => [
            'en' => 'about-us',
            'de' => 'ueber-uns',
            'fr' => 'a-propos',
            'es' => 'sobre-nosotros',
        ],
        'content' => snel_seed_content_section(
            '<!-- wp:heading --><h2>Over Ons</h2><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>Ons team bestaat uit ervaren professionals die gepassioneerd zijn over wat ze doen. Wij geloven in transparantie, kwaliteit en samenwerking.</p><!-- /wp:paragraph -->'
            . '<!-- wp:paragraph --><p>Sinds onze oprichting hebben wij honderden klanten geholpen met hun projecten. Ons doel is om altijd de beste resultaten te leveren.</p><!-- /wp:paragraph -->',
            [
                'en' => ['<h2>About Us</h2>', '<p>Our team consists of experienced professionals who are passionate about what they do. We believe in transparency, quality, and collaboration.</p>', '<p>Since our founding, we have helped hundreds of clients with their projects. Our goal is to always deliver the best results.</p>'],
                'de' => ['<h2>Über Uns</h2>', '<p>Unser Team besteht aus erfahrenen Fachleuten, die mit Leidenschaft bei der Sache sind. Wir glauben an Transparenz, Qualität und Zusammenarbeit.</p>', '<p>Seit unserer Gründung haben wir Hunderten von Kunden bei ihren Projekten geholfen. Unser Ziel ist es, immer die besten Ergebnisse zu liefern.</p>'],
                'fr' => ['<h2>À Propos</h2>', '<p>Notre équipe est composée de professionnels expérimentés et passionnés par leur métier. Nous croyons en la transparence, la qualité et la collaboration.</p>', '<p>Depuis notre création, nous avons aidé des centaines de clients dans leurs projets. Notre objectif est de toujours fournir les meilleurs résultats.</p>'],
                'es' => ['<h2>Sobre Nosotros</h2>', '<p>Nuestro equipo está formado por profesionales experimentados y apasionados por lo que hacen. Creemos en la transparencia, la calidad y la colaboración.</p>', '<p>Desde nuestra fundación, hemos ayudado a cientos de clientes con sus proyectos. Nuestro objetivo es ofrecer siempre los mejores resultados.</p>'],
            ]
        ),
    ],
    [
        'title'   => 'Diensten',
        'slug'    => 'diensten',
        'slugs'   => [
            'en' => 'services',
            'de' => 'dienstleistungen',
            'fr' => 'services',
            'es' => 'servicios',
        ],
        'content' => snel_seed_content_section(
            '<!-- wp:heading --><h2>Onze Diensten</h2><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>Wij bieden een breed scala aan diensten om aan uw behoeften te voldoen. Van advies tot uitvoering, wij staan voor u klaar.</p><!-- /wp:paragraph -->'
            . '<!-- wp:list --><ul><li>Webontwikkeling &amp; design</li><li>Digitale strategie &amp; advies</li><li>SEO &amp; online marketing</li><li>Onderhoud &amp; support</li></ul><!-- /wp:list -->',
            [
                'en' => ['<h2>Our Services</h2>', '<p>We offer a wide range of services to meet your needs. From consulting to execution, we are here for you.</p>', '<ul><li>Web development &amp; design</li><li>Digital strategy &amp; consulting</li><li>SEO &amp; online marketing</li><li>Maintenance &amp; support</li></ul>'],
                'de' => ['<h2>Unsere Dienstleistungen</h2>', '<p>Wir bieten ein breites Spektrum an Dienstleistungen, um Ihren Anforderungen gerecht zu werden. Von der Beratung bis zur Umsetzung stehen wir Ihnen zur Seite.</p>', '<ul><li>Webentwicklung &amp; Design</li><li>Digitale Strategie &amp; Beratung</li><li>SEO &amp; Online-Marketing</li><li>Wartung &amp; Support</li></ul>'],
                'fr' => ['<h2>Nos Services</h2>', '<p>Nous offrons une large gamme de services pour répondre à vos besoins. Du conseil à l\'exécution, nous sommes là pour vous.</p>', '<ul><li>Développement web &amp; design</li><li>Stratégie digitale &amp; conseil</li><li>SEO &amp; marketing en ligne</li><li>Maintenance &amp; support</li></ul>'],
                'es' => ['<h2>Nuestros Servicios</h2>', '<p>Ofrecemos una amplia gama de servicios para satisfacer sus necesidades. Desde consultoría hasta ejecución, estamos aquí para usted.</p>', '<ul><li>Desarrollo web &amp; diseño</li><li>Estrategia digital &amp; consultoría</li><li>SEO &amp; marketing online</li><li>Mantenimiento &amp; soporte</li></ul>'],
            ]
        ),
    ],
    [
        'title'   => 'Contact',
        'slug'    => 'contact',
        'slugs'   => [
            'en' => 'contact',
            'de' => 'kontakt',
            'fr' => 'contact',
            'es' => 'contacto',
        ],
        'content' => snel_seed_content_section(
            '<!-- wp:heading --><h2>Neem Contact Op</h2><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>Heeft u vragen of wilt u meer weten over onze diensten? Neem gerust contact met ons op via onderstaande gegevens.</p><!-- /wp:paragraph -->'
            . '<!-- wp:paragraph --><p><strong>E-mail:</strong> info@example.com<br><strong>Telefoon:</strong> +31 (0)20 123 4567<br><strong>Adres:</strong> Voorbeeldstraat 1, 1234 AB Amsterdam</p><!-- /wp:paragraph -->',
            [
                'en' => ['<h2>Get In Touch</h2>', '<p>Do you have questions or would you like to know more about our services? Feel free to contact us using the details below.</p>', '<p><strong>Email:</strong> info@example.com<br><strong>Phone:</strong> +31 (0)20 123 4567<br><strong>Address:</strong> Voorbeeldstraat 1, 1234 AB Amsterdam</p>'],
                'de' => ['<h2>Kontaktieren Sie Uns</h2>', '<p>Haben Sie Fragen oder möchten Sie mehr über unsere Dienstleistungen erfahren? Kontaktieren Sie uns gerne über die unten stehenden Angaben.</p>', '<p><strong>E-Mail:</strong> info@example.com<br><strong>Telefon:</strong> +31 (0)20 123 4567<br><strong>Adresse:</strong> Voorbeeldstraat 1, 1234 AB Amsterdam</p>'],
                'fr' => ['<h2>Contactez-Nous</h2>', '<p>Vous avez des questions ou souhaitez en savoir plus sur nos services ? N\'hésitez pas à nous contacter via les coordonnées ci-dessous.</p>', '<p><strong>E-mail :</strong> info@example.com<br><strong>Téléphone :</strong> +31 (0)20 123 4567<br><strong>Adresse :</strong> Voorbeeldstraat 1, 1234 AB Amsterdam</p>'],
                'es' => ['<h2>Contáctenos</h2>', '<p>¿Tiene preguntas o desea saber más sobre nuestros servicios? No dude en contactarnos a través de los datos que aparecen a continuación.</p>', '<p><strong>Correo electrónico:</strong> info@example.com<br><strong>Teléfono:</strong> +31 (0)20 123 4567<br><strong>Dirección:</strong> Voorbeeldstraat 1, 1234 AB Amsterdam</p>'],
            ]
        ),
    ],
    [
        'title'   => 'Privacybeleid',
        'slug'    => 'privacybeleid',
        'slugs'   => [
            'en' => 'privacy-policy',
            'de' => 'datenschutz',
            'fr' => 'politique-de-confidentialite',
            'es' => 'politica-de-privacidad',
        ],
        'content' => snel_seed_content_section(
            '<!-- wp:heading --><h2>Privacybeleid</h2><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>Wij respecteren uw privacy en verwerken uw persoonsgegevens in overeenstemming met de AVG. Dit privacybeleid beschrijft welke gegevens wij verzamelen en hoe wij deze gebruiken.</p><!-- /wp:paragraph -->',
            [
                'en' => ['<h2>Privacy Policy</h2>', '<p>We respect your privacy and process your personal data in accordance with the GDPR. This privacy policy describes what data we collect and how we use it.</p>'],
                'de' => ['<h2>Datenschutzerklärung</h2>', '<p>Wir respektieren Ihre Privatsphäre und verarbeiten Ihre personenbezogenen Daten in Übereinstimmung mit der DSGVO. Diese Datenschutzerklärung beschreibt, welche Daten wir erheben und wie wir sie verwenden.</p>'],
                'fr' => ['<h2>Politique de Confidentialité</h2>', '<p>Nous respectons votre vie privée et traitons vos données personnelles conformément au RGPD. Cette politique de confidentialité décrit les données que nous collectons et comment nous les utilisons.</p>'],
                'es' => ['<h2>Política de Privacidad</h2>', '<p>Respetamos su privacidad y procesamos sus datos personales de acuerdo con el RGPD. Esta política de privacidad describe qué datos recopilamos y cómo los utilizamos.</p>'],
            ]
        ),
    ],
];
