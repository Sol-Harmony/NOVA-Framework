<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Config    //reads the two config files. Config::get('mail.host'), Config::get('site.business.email')
{
    private static $data = null;

    //config/config.ini  = secrets and server settings (database, mail password, debug), NOT in git, differs per server
    //config/site.php    = content of the website (name, menu, address), in git, differs per client
    //the sections of the ini file are available by their name, the site.php array as "site"
    public static function load()
    {
        $data = [];

        $ini = BASEPATH . '/config/config.ini';
        if (is_file($ini)) {
            //INI_SCANNER_RAW: no value is changed, so a password like "none" or "null" stays as it is
            $parsed = parse_ini_file($ini, true, INI_SCANNER_RAW);
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        $site = BASEPATH . '/config/site.php';
        $siteData = is_file($site) ? include $site : [];
        $data['site'] = is_array($siteData) ? $siteData : [];

        self::$data = $data;
    }

    public static function get($key, $default = null)
    {
        if (self::$data === null) {
            self::load();
        }

        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    //a text that can be given per language: 'tagline' => ['de' => 'Hallo', 'en' => 'Hello'] gives the one of the visitor's language
    //(then english, then the first one). A plain text is returned as it is, so existing configs keep working
    public static function text($key, $default = '')
    {
        $value = self::get($key, $default);
        if (is_array($value)) {
            $value = $value[Lang::code()] ?? $value['en'] ?? (reset($value) ?: $default);
        }
        return is_scalar($value) ? (string) $value : (string) $default;
    }

    //for switches in the ini file: 1, true, on and yes are true, everything else is false
    public static function bool($key, $default = false)
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}
