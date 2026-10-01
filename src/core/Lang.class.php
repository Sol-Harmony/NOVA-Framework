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

    //language code, falls back to german when the config has something odd
    public static function code()
    {
        $code = Config::get('site.lang', 'de');
        return (is_string($code) && preg_match('/^[a-z]{2}$/', $code)) ? $code : 'de';
    }

    public static function t($key, array $replace = [])
    {
        //1. texts overridden for this client in config/site.php ('texts' => ['nav.home' => 'Welcome'])
        $overrides = Config::get('site.texts', []);
        $text = (is_array($overrides) && isset($overrides[$key]) && is_string($overrides[$key])) ? $overrides[$key] : null;

        //2. the language file, 3. english as fallback, 4. the key itself so a missing text is visible
        if ($text === null) {
            $text = self::load(self::code())[$key] ?? self::load('en')[$key] ?? $key;
        }

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
