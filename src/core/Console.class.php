<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Console    //terminal commands of the framework, started with: php nova <command>
{
    protected $basepath;

    public function __construct($basepath)
    {
        $this->basepath = $basepath;
    }

    //returns the exit code: 0 = ok, 1 = error
    public function run($argv)
    {
        $args = array_slice($argv, 1);
        $command = array_shift($args);

        switch ($command) {
            case 'check':
                return $this->check();
            case 'mail:test':
                return $this->mailTest($args);
            case null:
            case 'help':
                $this->help();
                return 0;
            default:
                $this->line("Unknown command: $command");
                $this->help();
                return 1;
        }
    }

    protected function help()
    {
        $this->line("Usage:");
        $this->line("  php nova check                        go-live checklist: config, folders, mail, placeholders");
        $this->line("  php nova mail:test <address>          sends a test mail with the settings from config/config.ini");
    }

    //checks everything that has to be right before a website goes live. exit code 1 when something is wrong (FAIL)
    protected function check()
    {
        $failed = false;
        $report = function ($status, $text, $hint = '') use (&$failed) {
            if ($status === 'FAIL') {
                $failed = true;
            }
            $this->line(str_pad("[$status]", 7) . ' ' . $text . ($status !== 'ok' && $hint !== '' ? "  → $hint" : ''));
        };

        $report(PHP_VERSION_ID >= 70400 ? 'ok' : 'FAIL', 'PHP ' . PHP_VERSION, 'PHP 7.4 or newer is needed');
        foreach (['mbstring', 'json', 'session', 'filter'] as $extension) {
            $report(extension_loaded($extension) ? 'ok' : 'FAIL', "PHP extension $extension");
        }
        if (Config::get('mail.host') && Config::get('mail.encryption', 'tls') !== 'none') {
            $report(extension_loaded('openssl') ? 'ok' : 'FAIL', 'PHP extension openssl (encrypted mail)');
        }
        if (Config::get('database')) {
            $report(extension_loaded('pdo_mysql') ? 'ok' : 'FAIL', 'PHP extension pdo_mysql (database)');
        }

        $report(is_file($this->basepath . '/config/config.ini') ? 'ok' : 'FAIL', 'config/config.ini exists', 'copy config/config.example.ini to config/config.ini and fill it in');
        $report(!DEBUG ? 'ok' : 'WARN', DEBUG ? 'debug is ON' : 'debug is off', 'fine on your computer, but "debug = 1" must not be on the live server');

        foreach (['storage', 'storage/logs', 'storage/sessions', 'storage/ratelimit', 'storage/outbox'] as $folder) {
            $path = $this->basepath . '/' . $folder;
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $report(is_dir($path) && is_writable($path) ? 'ok' : 'FAIL', "$folder is writable (for the user running nova)", 'the web server user needs write access. If the web server created this folder itself, this can be a false alarm: nova runs as another user');
        }

        //texts that still come from the template
        $placeholders = ['Name', 'Your Business', 'Muster GmbH', 'Max Mustermann', 'info@example.com', 'Musterstadt', 'Musterstraße 1'];
        $left = [];
        foreach (['name', 'business.name', 'business.owner', 'business.email', 'business.street', 'business.city'] as $key) {
            $value = Config::get('site.' . $key, '');
            if (!is_string($value) || $value === '' || in_array($value, $placeholders, true)) {
                $left[] = $key;
            }
        }
        $report(!$left ? 'ok' : 'WARN', 'config/site.php filled in', $left ? 'still empty or from the template: ' . implode(', ', $left) : '');
        $report(in_array(Lang::code(), ['de', 'en'], true) ? 'ok' : 'WARN', 'language ' . Lang::code(), 'Impressum and privacy pages exist in de and en only');

        $report(Seo::baseUrl() !== '' ? 'ok' : 'WARN', Seo::baseUrl() !== '' ? 'site url ' . Seo::baseUrl() : 'site url not set', "set 'url' in config/site.php (e.g. https://www.client.de): without it there is no sitemap and no canonical links");
        $report(!Seo::noindex() ? 'ok' : 'WARN', Seo::noindex() ? "'noindex' is ON: search engines are told to ignore this site" : 'search engines may index the site', "set 'noindex' => false in config/site.php when the site goes live");

        $mailTo = Config::get('mail.to') ?: Config::get('site.business.email');
        $mailTo = (string) $mailTo;
        $report($mailTo !== '' && $mailTo !== 'info@example.com' ? 'ok' : 'FAIL', 'contact form recipient: ' . ($mailTo ?: '(none)'), 'set "to" under [mail]');
        $report(Config::get('mail.host') ? 'ok' : 'WARN', Config::get('mail.host') ? 'mail through SMTP (' . Config::get('mail.host') . ')' : 'mail through PHP mail()', 'many hosters deliver this badly, set host, username and password under [mail] and run php nova mail:test');

        $this->line('');
        $this->line('Also check by hand: https works and the http→https redirect in .htaccess is on, the Impressum and privacy text fit the business,');
        $this->line('the contact form delivers (php nova mail:test), assets/theme.css and the picture in /pictures are the client\'s.');
        return $failed ? 1 : 0;
    }

    //php nova mail:test you@example.com
    protected function mailTest($args)
    {
        $to = $args[0] ?? null;
        if ($to === null) {
            $this->line("Missing address. Example: php nova mail:test you@example.com");
            return 1;
        }

        $via = Config::get('mail.host') ? 'SMTP ' . Config::get('mail.host') . ':' . (Config::get('mail.port') ?: 587) : 'PHP mail()';
        $this->line("Sending a test mail to $to through $via ...");
        if (Mail::send($to, 'Test mail from ' . Config::get('site.name', 'NOVA'), "If you read this, the mail settings work.\n\nSent " . date('Y-m-d H:i:s') . "\n")) {
            $this->line("Accepted by the mail server. Now check the inbox (and the spam folder).");
            return 0;
        }
        $this->line("Failed: " . Mail::lastError());
        return 1;
    }

    protected function line($text)
    {
        echo $text . PHP_EOL;
    }
}
