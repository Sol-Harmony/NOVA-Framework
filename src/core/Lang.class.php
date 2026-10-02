<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Lang      //texts of the website in the language from config/site.php ('lang' => 'de'), use t('key') in the views
{
    private static $strings = [];

    //languages the visitor can switch between in the navbar (one file each in src/lang/)
    public static function available()
    {
        return ['de', 'en'];
    }

    //the language of the website without a choice of the visitor: 'lang' in config/site.php, german when the config has something odd
    public static function configured()
    {
        $code = Config::get('site.lang', 'de');
        return (is_string($code) && preg_match('/^[a-z]{2}$/', $code)) ? $code : 'de';
    }

    //language code: the visitor's choice from the navbar (?lang=en in the address), otherwise the language from config/site.php.
    //the choice lives in the address only, nothing is stored on the visitor's device, so no cookie (and no cookie notice) is needed
    public static function code()
    {
        $chosen = $_GET['lang'] ?? null;
        if (is_string($chosen) && in_array($chosen, self::available(), true)) {
            return $chosen;
        }
        return self::configured();
    }

    //adds the chosen language to a link of this website, so it stays when the visitor clicks through the pages.
    //the language of the config needs no addition: "/kontakt" stays "/kontakt"
    public static function withLang($url)
    {
        $code = self::code();
        if ($code === self::configured()) {
            return $url;
        }
        return $url . (strpos($url, '?') === false ? '?' : '&') . 'lang=' . $code;
    }

    public static function t($key, array $replace = [])
    {
        //the language file, english as fallback, the key itself so a missing text is visible
        $text = self::load(self::code())[$key] ?? self::load('en')[$key] ?? $key;

        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }
        return $text;
    }

    private static function load($code)
    {
        if (!isset(self::$strings[$code])) {
            $file = BASEPATH . '/src/lang/' . $code . '.php';
            $strings = is_file($file) ? include $file : [];
            self::$strings[$code] = is_array($strings) ? $strings : [];
        }
        return self::$strings[$code];
    }
}
