<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Seo       //things for search engines: canonical url, sitemap, and the business data for Google (schema.org, JSON-LD)
{
    //address of the website from config/site.php ('url' => 'https://www.example.com', no slash at the end).
    //it is never taken from the request: the Host header comes from the visitor and could be faked
    public static function baseUrl()
    {
        $url = Config::get('site.url');
        return (is_string($url) && preg_match('#^https?://[A-Za-z0-9.-]+(:\d+)?$#', $url)) ? $url : '';
    }

    //'noindex' => true in config/site.php while a site is not ready (test address): no search engine lists it
    public static function noindex()
    {
        return Config::bool('site.noindex');
    }

    //full address for a path of this website: '/kontakt' → 'https://www.example.com/kontakt'. empty when site.url is not set
    public static function absolute($path)
    {
        $base = self::baseUrl();
        if ($base === '' || !is_string($path) || $path === '' || $path[0] !== '/') {
            return '';
        }
        return $base . $path;
    }

    //all pages for sitemap.xml: the menu plus the legal pages
    public static function pages()
    {
        $paths = [];
        $nav = Config::get('site.nav', []);
        foreach (is_array($nav) ? $nav : [] as $item) {
            if (isset($item['url']) && is_string($item['url']) && $item['url'] !== '' && $item['url'][0] === '/') {
                $paths[] = $item['url'];
            }
        }
        $paths[] = Utils::url('impressum');
        $paths[] = Utils::url('privacy');
        return array_values(array_unique($paths));
    }

    //business data for Google as JSON, put into <script type="application/ld+json">. '' if it can't be built.
    //the flags make "</script>" and quotes in the data harmless
    public static function jsonLd()
    {
        $b = Config::get('site.business', []);
        $b = is_array($b) ? $b : [];
        $text = function ($key) use ($b) {
            return isset($b[$key]) && is_scalar($b[$key]) ? trim((string) $b[$key]) : '';
        };

        //the kind of business, e.g. Restaurant, Hairdresser, Plumber, Store (schema.org/LocalBusiness has the list)
        $type = $text('type');
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => preg_match('/^[A-Za-z]+$/', $type) ? $type : 'LocalBusiness',
            'name'     => $text('name') ?: (string) Config::get('site.name', ''),
        ];

        $url = self::absolute('/');
        if ($url !== '') {
            $data['url'] = $url;
        }
        $description = (string) Config::get('site.description', '');
        if ($description !== '') {
            $data['description'] = $description;
        }
        $image = self::absolute((string) Config::get('site.image', ''));
        if ($image !== '') {
            $data['image'] = $image;
        }
        if ($text('phone') !== '') {
            $data['telephone'] = $text('phone');
        }
        if ($text('email') !== '') {
            $data['email'] = $text('email');
        }

        if ($text('street') !== '' || $text('city') !== '') {
            $address = ['@type' => 'PostalAddress'];
            foreach (['streetAddress' => 'street', 'postalCode' => 'zip', 'addressLocality' => 'city', 'addressCountry' => 'country'] as $schema => $key) {
                if ($text($key) !== '') {
                    $address[$schema] = $text($key);
                }
            }
            $data['address'] = $address;
        }

        $hours = self::openingHours($b['hours'] ?? []);
        if ($hours) {
            $data['openingHoursSpecification'] = $hours;
        }

        $social = [];
        $links = Config::get('site.social', []);
        foreach (is_array($links) ? $links : [] as $link) {
            if (is_string($link) && preg_match('#^https?://#', $link)) {
                $social[] = $link;
            }
        }
        if ($social) {
            $data['sameAs'] = $social;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return $json === false ? '' : $json;
    }

    //opening hours of site.php ([['Mo – Fr', '09:00 – 18:00'], ['Sa', '10:00 – 14:00']]) as schema.org specifications.
    //understands german (Mo Di Mi Do Fr Sa So) and english (Mon Tue Wed Thu Fri Sat Sun) days, ranges ("Mo – Fr") and lists
    //("Mo, Mi, Fr"), and several times in one row ("09:00 – 12:00, 14:00 – 18:00"). Rows it doesn't understand ("closed") are skipped
    public static function openingHours($rows)
    {
        $names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $index = ['mo' => 0, 'di' => 1, 'tu' => 1, 'mi' => 2, 'we' => 2, 'do' => 3, 'th' => 3, 'fr' => 4, 'sa' => 5, 'so' => 6, 'su' => 6];
        $specs = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || !isset($row[0], $row[1]) || !is_string($row[0]) || !is_string($row[1])) {
                continue;
            }

            //days
            $days = [];
            $parts = preg_split('/\s*(?:,|&|\bund\b|\band\b)\s*/iu', trim($row[0]));
            foreach ($parts as $part) {
                $ends = preg_split('/\s*(?:–|—|-|\bbis\b|\bto\b)\s*/iu', trim($part));
                $first = $index[strtolower(substr(preg_replace('/[^A-Za-z]/', '', $ends[0]), 0, 2))] ?? null;
                if ($first === null) {
                    continue;
                }
                $last = isset($ends[1]) ? ($index[strtolower(substr(preg_replace('/[^A-Za-z]/', '', $ends[1]), 0, 2))] ?? null) : $first;
                if ($last === null) {
                    continue;
                }
                for ($d = $first; ; $d = ($d + 1) % 7) {     //"Sa – Mo" wraps around the week
                    $days[$names[$d]] = true;
                    if ($d === $last) {
                        break;
                    }
                }
            }

            //times
            preg_match_all('/(\d{1,2})[:.](\d{2})\s*(?:–|—|-|bis|to)\s*(\d{1,2})[:.](\d{2})/u', $row[1], $times, PREG_SET_ORDER);
            if (!$days || !$times) {
                continue;
            }
            foreach ($times as $time) {
                $specs[] = [
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => array_keys($days),
                    'opens'     => sprintf('%02d:%02d', $time[1], $time[2]),
                    'closes'    => sprintf('%02d:%02d', $time[3], $time[4]),
                ];
            }
        }
        return $specs;
    }
}
