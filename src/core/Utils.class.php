<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Utils     //small helpers. the views use them through the functions in helpers.php: e(), asset(), route()
{
    //escapes text for html, so a visitor's input can't inject tags or scripts into a page
    public static function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    //true when the visitor is connected over https. behind a proxy/load balancer set "trust_proxy = 1" under [security]
    public static function isHttps()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? '') == 443) {
            return true;
        }
        return Config::bool('security.trust_proxy') && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    //true when the caller wants JSON back (javascript requests), so errors don't arrive as a html page
    public static function wantsJson()
    {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false && stripos($accept, 'text/html') === false;
    }

    //"/assets/style.css" → "/assets/style.css?v=1700000000": the number changes with the file, so browsers can cache it for long
    //and still load a new version right away
    public static function asset($path)
    {
        $file = BASEPATH . $path;
        return $path . '?v=' . (is_file($file) ? filemtime($file) : 0);
    }

    //links that open directions to an address in a maps app: ['Google Maps' => url, 'Apple Maps' => url]. no map is embedded, so
    //nothing is loaded from another website until the visitor clicks. without an address the business address from
    //config/site.php is used, empty array = no address
    public static function mapLinks($address = null)
    {
        if ($address === null) {
            $b = Config::get('site.business', []);
            $parts = [];
            foreach (['street', 'zip', 'city', 'country'] as $key) {
                if (is_array($b) && isset($b[$key]) && is_scalar($b[$key]) && trim((string) $b[$key]) !== '') {
                    $parts[] = trim((string) $b[$key]);
                }
            }
            if (!isset($b['street']) || !isset($b['city'])) {
                return [];
            }
            $address = implode(', ', $parts);
        }

        $address = trim((string) $address);
        if ($address === '') {
            return [];
        }
        $encoded = rawurlencode($address);
        return [
            'Google Maps' => 'https://www.google.com/maps/dir/?api=1&destination=' . $encoded,
            'Apple Maps'  => 'https://maps.apple.com/?daddr=' . $encoded,
        ];
    }

    //url of a controller (and action), uses the pretty urls from 'routes' in config/site.php: url('contact') → /kontakt
    //$keepLang false = without the "?lang=" of the visitor's language (canonical links, sitemap)
    public static function url($controller, $action = null, $keepLang = true)
    {
        $path = self::path($controller, $action);
        return $keepLang ? Lang::withLang($path) : $path;
    }

    private static function path($controller, $action)
    {
        $controller = strtolower($controller);
        if ($controller === 'home' && $action === null) {
            return '/';
        }

        $path = '/' . $controller;
        $routes = Config::get('site.routes', []);
        if (is_array($routes)) {
            foreach ($routes as $alias => $target) {
                if (is_string($target) && strtolower(trim($target, '/')) === $controller) {
                    $path = '/' . $alias;
                    break;
                }
            }
        }
        return $action === null ? $path : $path . '/' . $action;
    }
}
