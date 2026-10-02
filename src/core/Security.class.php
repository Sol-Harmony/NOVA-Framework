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
    //if a client needs something external (a Google map, a video), add its address to the list below, and update the privacy page and cookie consent
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

        $parts = [];
        foreach ($directives as $name => $sources) {
            $parts[] = $name . ' ' . implode(' ', $sources);
        }
        return implode('; ', $parts);
    }
}
