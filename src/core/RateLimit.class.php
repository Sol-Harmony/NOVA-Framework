<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class RateLimit     //limits how often something can be done, e.g. 5 contact messages per hour and visitor
{
    const KEEP = 86400;     //seconds until the file of a visitor is deleted, whatever the limit is

    //returns true and counts the attempt, or false when the limit is already reached.
    //$key tells what is limited and for whom: 'contact:' . $request->getIp(). only a hash of it is stored on disk
    public static function hit($key, $max, $seconds)
    {
        $dir = BASEPATH . '/storage/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log('RateLimit: ' . $dir . ' is not writable, the limit is not enforced.');
            return true;
        }

        $handle = fopen($dir . '/' . self::fileKey($dir, $key) . '.json', 'c+');
        if ($handle === false) {
            return true;
        }

        $allowed = true;
        if (flock($handle, LOCK_EX)) {     //two requests at the same time can't both pass
            $now = time();
            $times = json_decode((string) stream_get_contents($handle), true);
            $times = is_array($times) ? array_values(array_filter($times, fn($t) => is_int($t) && $t > $now - $seconds)) : [];

            if (count($times) >= $max) {
                $allowed = false;
            } else {
                $times[] = $now;
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($times));
            fflush($handle);
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        if (mt_rand(1, 50) === 1) {
            self::cleanup($dir);
        }
        return $allowed;
    }

    //name of the file of a visitor. a plain hash of an IP address can be guessed (there are only 4 billion of them), so the hash
    //includes a secret that is created once and never leaves the server: nobody can get an IP address out of the file names
    private static function fileKey($dir, $key)
    {
        $saltFile = $dir . '/.salt';
        $salt = is_file($saltFile) ? (string) @file_get_contents($saltFile) : '';
        if (strlen($salt) < 32) {
            $salt = bin2hex(random_bytes(32));
            @file_put_contents($saltFile, $salt, LOCK_EX);
        }
        return hash_hmac('sha256', $key, $salt);
    }

    private static function cleanup($dir)
    {
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - self::KEEP) {
                @unlink($file);
            }
        }
    }
}
