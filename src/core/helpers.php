<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/

//short functions for the views (loaded by src/bootstrap.php)

//escapes a value for html. use it for everything that is printed into a page, e.g. e($name) (a "close php tag" in a // comment would end php mode)
function e($value)
{
    return Utils::e($value);
}

//translated text from src/lang/<language>.php, see Lang
function t($key, array $replace = [])
{
    return Lang::t($key, $replace);
}

//path of a file in /assets with the change time attached, so browsers reload it when it changed
function asset($path)
{
    return Utils::asset($path);
}

//url of a page, respects the pretty urls from config/site.php: route('contact') → /kontakt
function route($controller, $action = null)
{
    return Utils::url($controller, $action);
}

//hidden form field with the csrf token, put it inside every <form method="post">
function csrf_field()
{
    return Csrf::field();
}

//a value from the "business" block of config/site.php, empty text when it is not set
function biz($key)
{
    $value = Config::get('site.business.' . $key, '');
    return is_scalar($value) ? (string) $value : '';
}
