<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Csrf      //protects forms against requests sent from other websites
{
    const FIELD = '_csrf';

    //the token of the current session, created on first use
    public static function token()
    {
        Session::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    //<input type="hidden"> for inside the form
    public static function field()
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . Utils::e(self::token()) . '">';
    }

    //checks the token of a POST request. javascript requests can send it in the header X-CSRF-Token instead of the form field.
    //the Router calls this for every request that changes something, a controller doesn't have to
    public static function validate()
    {
        //browsers tell if a request comes from another website, those never pass
        if (strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
            return false;
        }

        //no session cookie means no token was ever handed out. don't create a session for that
        if (!Session::exists()) {
            return false;
        }
        Session::start();

        $sent = $_POST[self::FIELD] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $known = $_SESSION['_csrf'] ?? '';
        return is_string($sent) && $sent !== '' && is_string($known) && $known !== '' && hash_equals($known, $sent);
    }
}
