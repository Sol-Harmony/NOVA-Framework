<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Security  //security headers, sent with every response (index.php calls sendHeaders())
{
    public static function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');                              //browsers don't guess file types
        header('X-Frame-Options: SAMEORIGIN');                                  //other websites can't put this site in a frame
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Content-Security-Policy: ' . self::csp());
        header_remove('X-Powered-By');

        //only on https, and not while developing (browsers remember this for a year)
        if (Utils::isHttps() && !(defined('DEBUG') && DEBUG)) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    //content security policy: the browser only loads scripts, styles, images and fonts from this website.
    //if a client needs something external (a Google map, a video), add it in config/site.php:
    //  'csp' => ['frame-src' => ['https://www.google.com'], 'img-src' => ['https://images.example.com']]
    public static function csp()
    {
        $directives = [
            'default-src'     => ["'self'"],
            'img-src'         => ["'self'", 'data:'],
            'style-src'       => ["'self'"],
            'script-src'      => ["'self'"],
            'font-src'        => ["'self'"],
            'connect-src'     => ["'self'"],
            'form-action'     => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'base-uri'        => ["'self'"],
            'object-src'      => ["'none'"],
        ];

        $extra = Config::get('site.csp', []);
        if (is_array($extra)) {
            foreach ($extra as $directive => $sources) {
                if (!is_string($directive) || !preg_match('/^[a-z-]+$/', $directive) || !is_array($sources)) {
                    continue;
                }
                if (!isset($directives[$directive])) {
                    $directives[$directive] = ["'self'"];
                }
                foreach ($sources as $source) {
                    //no spaces, semicolons or commas: one source must not be able to add a new directive
                    if (is_string($source) && preg_match('/^[^\s;,]+$/', $source) && !in_array($source, $directives[$directive], true)) {
                        $directives[$directive][] = $source;
                    }
                }
            }
        }

        $parts = [];
        foreach ($directives as $name => $sources) {
            $parts[] = $name . ' ' . implode(' ', $sources);
        }
        return implode('; ', $parts);
    }
}
