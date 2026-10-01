<?php

/**
 * Content of this website. This is the file you edit for every new client.
 * It is part of the project (in git). Passwords and server settings do NOT belong here, they go into config/config.ini.
 *
 * Colors, fonts and shapes are in assets/theme.css, texts of the standard pages in src/lang/ (or 'texts' below).
 */
return [
    'name'        => 'Ihr Unternehmen',                                 //shown in the header, the footer and the <title> of every page
    'tagline'     => 'Ein kurzer Satz darüber, was Sie anbieten',        //title of the home page, next to the name
    'description' => 'Standardbeschreibung für Suchmaschinen, etwa 150 Zeichen lang. Jede Seite kann eine eigene setzen.',
    'lang'        => 'de',                                              //de or en (files in src/lang/)
    'timezone'    => 'Europe/Berlin',

    //address of the live website, without a slash at the end. Needed for the sitemap, the canonical links and Google's business data.
    //Set it when the site goes live: 'https://www.client.de'
    'url'         => '',
    //picture that appears when a page is shared (WhatsApp, Facebook, ...) and in the business data for Google
    'image'       => '/pictures/home/home1.webp',
    //true while the site is not ready (test address): search engines are told to stay away. Set to false when it goes live!
    'noindex'     => false,

    //menu. 'label' is a text key from src/lang/ or simply the text itself
    'nav' => [
        ['label' => 'nav.home',     'url' => '/'],
        ['label' => 'nav.services', 'url' => '/leistungen'],
        ['label' => 'nav.gallery',  'url' => '/galerie'],
        ['label' => 'nav.contact',  'url' => '/kontakt'],
    ],

    //pretty urls: /kontakt opens the controller "contact" (src/controller/Contact.class.php), /datenschutz opens "privacy"
    'routes' => [
        'kontakt'     => 'contact',
        'leistungen'  => 'services',
        'galerie'     => 'gallery',
        'datenschutz' => 'privacy',
    ],

    //data of the business, used for the footer, the contact page, the Impressum and the business data for Google.
    //Leave a field empty to hide it. For the Impressum fill in everything the law requires for this business (see README, "Legal pages")
    'business' => [
        'type'        => 'LocalBusiness',               //kind of business for Google: Restaurant, Hairdresser, Plumber, Store, ... (schema.org/LocalBusiness)
        'name'        => 'Muster GmbH',                 //legal name
        'owner'       => 'Max Mustermann',              //owner or managing director
        'street'      => 'Musterstraße 1',
        'zip'         => '12345',
        'city'        => 'Musterstadt',
        'country'     => 'Deutschland',
        'phone'       => '+49 123 456789',              //also gives phones a "Call now" bar at the bottom of the screen
        'email'       => 'info@example.com',
        'vat_id'      => '',                            //USt-IdNr., e.g. DE123456789
        'register'    => '',                            //e.g. Amtsgericht Musterstadt, HRB 12345
        'responsible' => '',                            //person responsible for the content, if different from the owner
        'hosting'     => '',                            //name and address of the hoster, for the privacy page
        'map_url'     => '',                            //link behind "get directions". Empty = Google Maps search for the address above
        //opening hours: [days, times]. Also sent to Google, so keep this form: days like "Mo – Fr", "Sa" or "Mo, Mi, Fr",
        //times like "09:00 – 18:00" or "09:00 – 12:00, 14:00 – 18:00". A row like ['So', 'geschlossen'] is shown but not sent to Google
        'hours'       => [
            ['Mo – Fr', '09:00 – 18:00'],
            ['Sa',      '10:00 – 14:00'],
        ],
    ],

    //links shown in the footer and sent to Google (label => address). Leave empty for none
    //'social' => ['Instagram' => 'https://www.instagram.com/client', 'Facebook' => 'https://www.facebook.com/client'],
    'social' => [],

    //page /leistungen: one card per entry. 'price' is optional. (Texts in the language of the site)
    'services' => [
        ['title' => 'Leistung 1', 'text' => 'Kurze Beschreibung: Was bekommt der Kunde, und wie läuft es ab?', 'price' => 'ab 49 €'],
        ['title' => 'Leistung 2', 'text' => 'Beschreiben Sie auch diese Leistung in ein, zwei Sätzen.',        'price' => 'ab 89 €'],
        ['title' => 'Leistung 3', 'text' => 'Und eine dritte. Weitere Karten einfach als neue Zeile ergänzen.', 'price' => 'auf Anfrage'],
    ],

    //page /galerie shows every picture in the folder /pictures/gallery (sorted by file name). Delete the sample pictures there and
    //put the client's in (about 1920 px wide, WebP or JPG, under 300 KB). The description for visitors who can't see the picture
    //and for Google comes from the file name ("shop-front.webp" → "shop front"). Better text per picture:
    //'gallery_alts' => ['shop-front.webp' => 'The shop front in spring'],
    'gallery_alts' => [],

    //allow something from another website, e.g. an embedded Google map. Think about the privacy page and cookie consent first!
    //(the "get directions" link needs none of this: it only opens Google Maps when the visitor clicks it)
    //'csp' => ['frame-src' => ['https://www.google.com']],
    'csp' => [],

    //replace single texts of src/lang/ for this client without touching the language files
    //'texts' => ['nav.home' => 'Willkommen'],
    'texts' => [],
];
