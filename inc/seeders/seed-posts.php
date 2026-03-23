<?php

/**
 * Blog post seed data.
 *
 * Each post has:
 *   - title    — Post title (Dutch, the default language)
 *   - slug     — WordPress slug (Dutch)
 *   - slugs    — Translated slugs per language
 *   - content  — Post content (Gutenberg blocks with content-section)
 *   - excerpt  — Short excerpt (Dutch)
 *
 * Edit this file per project.
 *
 * @package Snel
 */

return [
    [
        'title'   => 'Onze nieuwe website is live',
        'slug'    => 'onze-nieuwe-website-is-live',
        'slugs'   => [
            'en' => 'our-new-website-is-live',
            'de' => 'unsere-neue-website-ist-live',
            'fr' => 'notre-nouveau-site-est-en-ligne',
            'es' => 'nuestro-nuevo-sitio-web-esta-en-linea',
        ],
        'excerpt' => 'Wij zijn trots om onze volledig vernieuwde website te presenteren.',
        'content' => snel_seed_content_section(
            '<!-- wp:paragraph --><p>Wij zijn trots om onze volledig vernieuwde website te presenteren. Met een fris design en verbeterde functionaliteit willen wij u een nog betere ervaring bieden.</p><!-- /wp:paragraph -->'
            . '<!-- wp:paragraph --><p>De nieuwe website is sneller, mobielvriendelijker en beschikbaar in meerdere talen. Wij blijven investeren in technologie om u beter van dienst te zijn.</p><!-- /wp:paragraph -->',
            [
                'en' => '<p>We are proud to present our completely renewed website. With a fresh design and improved functionality, we want to offer you an even better experience.</p><p>The new website is faster, more mobile-friendly, and available in multiple languages. We continue to invest in technology to serve you better.</p>',
                'de' => '<p>Wir sind stolz, unsere komplett erneuerte Website zu präsentieren. Mit einem frischen Design und verbesserter Funktionalität möchten wir Ihnen ein noch besseres Erlebnis bieten.</p><p>Die neue Website ist schneller, mobilfreundlicher und in mehreren Sprachen verfügbar. Wir investieren weiterhin in Technologie, um Sie besser bedienen zu können.</p>',
                'fr' => '<p>Nous sommes fiers de vous présenter notre site web entièrement renouvelé. Avec un design frais et des fonctionnalités améliorées, nous souhaitons vous offrir une expérience encore meilleure.</p><p>Le nouveau site est plus rapide, plus adapté aux mobiles et disponible en plusieurs langues. Nous continuons d\'investir dans la technologie pour mieux vous servir.</p>',
                'es' => '<p>Estamos orgullosos de presentar nuestro sitio web completamente renovado. Con un diseño fresco y funcionalidad mejorada, queremos ofrecerle una experiencia aún mejor.</p><p>El nuevo sitio web es más rápido, más adaptado a móviles y está disponible en varios idiomas. Seguimos invirtiendo en tecnología para servirle mejor.</p>',
            ]
        ),
    ],
    [
        'title'   => '5 tips voor een succesvolle online strategie',
        'slug'    => '5-tips-voor-een-succesvolle-online-strategie',
        'slugs'   => [
            'en' => '5-tips-for-a-successful-online-strategy',
            'de' => '5-tipps-fuer-eine-erfolgreiche-online-strategie',
            'fr' => '5-conseils-pour-une-strategie-en-ligne-reussie',
            'es' => '5-consejos-para-una-estrategia-online-exitosa',
        ],
        'excerpt' => 'Ontdek onze vijf praktische tips om uw online aanwezigheid te versterken.',
        'content' => snel_seed_content_section(
            '<!-- wp:paragraph --><p>In de digitale wereld van vandaag is een sterke online aanwezigheid essentieel. Hier zijn vijf tips om u op weg te helpen.</p><!-- /wp:paragraph -->'
            . '<!-- wp:list {"ordered":true} --><ol><li><strong>Ken uw doelgroep</strong> — Begrijp wie uw klanten zijn en wat ze zoeken.</li><li><strong>Investeer in SEO</strong> — Zorg dat uw website vindbaar is in zoekmachines.</li><li><strong>Wees consistent</strong> — Publiceer regelmatig waardevolle content.</li><li><strong>Gebruik social media</strong> — Bereik uw doelgroep waar ze zich bevinden.</li><li><strong>Meet en optimaliseer</strong> — Gebruik data om uw strategie te verbeteren.</li></ol><!-- /wp:list -->',
            [
                'en' => '<p>In today\'s digital world, a strong online presence is essential. Here are five tips to get you started.</p><ol><li><strong>Know your audience</strong> — Understand who your customers are and what they are looking for.</li><li><strong>Invest in SEO</strong> — Make sure your website is findable in search engines.</li><li><strong>Be consistent</strong> — Publish valuable content regularly.</li><li><strong>Use social media</strong> — Reach your audience where they are.</li><li><strong>Measure and optimize</strong> — Use data to improve your strategy.</li></ol>',
                'de' => '<p>In der heutigen digitalen Welt ist eine starke Online-Präsenz unerlässlich. Hier sind fünf Tipps, die Ihnen den Einstieg erleichtern.</p><ol><li><strong>Kennen Sie Ihre Zielgruppe</strong> — Verstehen Sie, wer Ihre Kunden sind und was sie suchen.</li><li><strong>Investieren Sie in SEO</strong> — Stellen Sie sicher, dass Ihre Website in Suchmaschinen auffindbar ist.</li><li><strong>Seien Sie konsistent</strong> — Veröffentlichen Sie regelmäßig wertvolle Inhalte.</li><li><strong>Nutzen Sie Social Media</strong> — Erreichen Sie Ihre Zielgruppe dort, wo sie sich aufhält.</li><li><strong>Messen und optimieren</strong> — Nutzen Sie Daten, um Ihre Strategie zu verbessern.</li></ol>',
                'fr' => '<p>Dans le monde numérique d\'aujourd\'hui, une forte présence en ligne est essentielle. Voici cinq conseils pour vous lancer.</p><ol><li><strong>Connaissez votre public</strong> — Comprenez qui sont vos clients et ce qu\'ils recherchent.</li><li><strong>Investissez dans le SEO</strong> — Assurez-vous que votre site web est trouvable dans les moteurs de recherche.</li><li><strong>Soyez cohérent</strong> — Publiez régulièrement du contenu de valeur.</li><li><strong>Utilisez les réseaux sociaux</strong> — Atteignez votre public là où il se trouve.</li><li><strong>Mesurez et optimisez</strong> — Utilisez les données pour améliorer votre stratégie.</li></ol>',
                'es' => '<p>En el mundo digital de hoy, una fuerte presencia en línea es esencial. Aquí hay cinco consejos para comenzar.</p><ol><li><strong>Conozca a su audiencia</strong> — Comprenda quiénes son sus clientes y qué buscan.</li><li><strong>Invierta en SEO</strong> — Asegúrese de que su sitio web sea encontrable en los motores de búsqueda.</li><li><strong>Sea consistente</strong> — Publique contenido valioso de forma regular.</li><li><strong>Use las redes sociales</strong> — Llegue a su audiencia donde se encuentra.</li><li><strong>Mida y optimice</strong> — Use datos para mejorar su estrategia.</li></ol>',
            ]
        ),
    ],
    [
        'title'   => 'Waarom meertaligheid belangrijk is voor uw bedrijf',
        'slug'    => 'waarom-meertaligheid-belangrijk-is',
        'slugs'   => [
            'en' => 'why-multilingual-matters-for-your-business',
            'de' => 'warum-mehrsprachigkeit-wichtig-ist',
            'fr' => 'pourquoi-le-multilinguisme-est-important',
            'es' => 'por-que-el-multilingueismo-es-importante',
        ],
        'excerpt' => 'Een meertalige website opent deuren naar nieuwe markten en klanten.',
        'content' => snel_seed_content_section(
            '<!-- wp:paragraph --><p>Een meertalige website is meer dan alleen een vertaling. Het is een investering in uw toekomst. Door uw content in meerdere talen aan te bieden, bereikt u nieuwe markten en bouwt u vertrouwen op bij internationale klanten.</p><!-- /wp:paragraph -->'
            . '<!-- wp:paragraph --><p>Onderzoek toont aan dat 75% van de consumenten de voorkeur geeft aan het kopen van producten in hun eigen taal. Mis deze kans niet en maak uw website vandaag nog meertalig.</p><!-- /wp:paragraph -->',
            [
                'en' => '<p>A multilingual website is more than just a translation. It is an investment in your future. By offering your content in multiple languages, you reach new markets and build trust with international customers.</p><p>Research shows that 75% of consumers prefer to buy products in their own language. Don\'t miss this opportunity and make your website multilingual today.</p>',
                'de' => '<p>Eine mehrsprachige Website ist mehr als nur eine Übersetzung. Sie ist eine Investition in Ihre Zukunft. Indem Sie Ihre Inhalte in mehreren Sprachen anbieten, erreichen Sie neue Märkte und bauen Vertrauen bei internationalen Kunden auf.</p><p>Studien zeigen, dass 75 % der Verbraucher es vorziehen, Produkte in ihrer eigenen Sprache zu kaufen. Verpassen Sie diese Chance nicht und machen Sie Ihre Website noch heute mehrsprachig.</p>',
                'fr' => '<p>Un site web multilingue est bien plus qu\'une simple traduction. C\'est un investissement dans votre avenir. En proposant votre contenu dans plusieurs langues, vous atteignez de nouveaux marchés et renforcez la confiance de vos clients internationaux.</p><p>Les études montrent que 75 % des consommateurs préfèrent acheter des produits dans leur propre langue. Ne manquez pas cette opportunité et rendez votre site multilingue dès aujourd\'hui.</p>',
                'es' => '<p>Un sitio web multilingüe es más que una simple traducción. Es una inversión en su futuro. Al ofrecer su contenido en varios idiomas, alcanza nuevos mercados y genera confianza con clientes internacionales.</p><p>Las investigaciones muestran que el 75% de los consumidores prefieren comprar productos en su propio idioma. No pierda esta oportunidad y haga su sitio web multilingüe hoy mismo.</p>',
            ]
        ),
    ],
];
