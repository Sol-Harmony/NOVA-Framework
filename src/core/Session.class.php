<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Session   //safe session handling. the session only starts when a page needs it (a form, a login), not on every request
{
    const LIFETIME = 7200;      //seconds without a request until the session is forgotten

    //name of the session cookie. "__Host-" makes browsers accept it only from this exact site over https
    public static function name()
    {
        return Utils::isHttps() ? '__Host-nova_session' : 'nova_session';
    }

    //true when the browser sent a session cookie (so there is something to continue)
    public static function exists()
    {
        return isset($_COOKIE[self::name()]);
    }

    public static function start()
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');        //the server never accepts a session id it didn't create
        ini_set('session.use_only_cookies', '1');       //no session id in the url
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) self::LIFETIME);

        //own folder: on shared hosting the default folder is shared with other websites
        $dir = BASEPATH . '/storage/sessions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $ownFolder = is_dir($dir) && is_writable($dir);
        if ($ownFolder) {
            session_save_path($dir);
        }

        session_name(self::name());
        session_set_cookie_params([
            'lifetime' => 0,                    //deleted when the browser is closed
            'path'     => '/',
            'secure'   => Utils::isHttps(),
            'httponly' => true,                 //not readable by javascript
            'samesite' => 'Lax',                //not sent along with cross-site POST requests
        ]);
        session_start();

        //too long without a request: start over with an empty session and a new id
        $now = time();
        if (isset($_SESSION['_last']) && $now - $_SESSION['_last'] > self::LIFETIME) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last'] = $now;

        //php doesn't clean a custom folder on every server, so do it here now and then
        if ($ownFolder && mt_rand(1, 100) === 1) {
            foreach (glob($dir . '/sess_*') ?: [] as $file) {
                if (is_file($file) && filemtime($file) < $now - self::LIFETIME) {
                    @unlink($file);
                }
            }
        }
    }

    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }

    //a message that survives exactly one redirect (e.g. "Thanks, message sent")
    public static function flash($key, $value)
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    //reads a flash message and removes it
    public static function getFlash($key, $default = null)
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    //call after a login: a new session id, so an id that was known before the login is worthless
    public static function regenerate()
    {
        self::start();
        session_regenerate_id(true);
    }

    //call on logout
    public static function destroy()
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }
}
