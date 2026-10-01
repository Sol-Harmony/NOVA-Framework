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

    //php keywords and type names, a class can't be called like that
    protected $reserved = [
        'abstract', 'and', 'array', 'as', 'bool', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const',
        'continue', 'declare', 'default', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor',
        'endforeach', 'endif', 'endswitch', 'endwhile', 'enum', 'eval', 'exit', 'extends', 'false', 'final',
        'finally', 'float', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include',
        'include_once', 'instanceof', 'insteadof', 'int', 'interface', 'isset', 'iterable', 'list', 'match',
        'mixed', 'namespace', 'never', 'new', 'null', 'object', 'or', 'parent', 'print', 'private', 'protected',
        'public', 'readonly', 'require', 'require_once', 'return', 'self', 'static', 'string', 'switch', 'throw',
        'trait', 'true', 'try', 'unset', 'use', 'var', 'void', 'while', 'xor', 'yield',
    ];

    //classes of the framework itself, a page can't have the same name
    protected $framework = [
        'autoloader', 'console', 'controller', 'request', 'router', 'utils',
        'basemodel', 'dbconnect', 'rowmodel', 'validate',
        'config', 'csrf', 'lang', 'mail', 'ratelimit', 'security', 'seo', 'session',
    ];

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
            case 'make':
                return $this->make($args);
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
        $this->line("  php nova make <Name>                  controller, model and view for the table <name>");
        $this->line("  php nova make <Name> --table=<table>  same, with a different table name");
        $this->line("  php nova make <Name> --no-model       only controller and view, no database");
        $this->line("  php nova check                        go-live checklist: config, folders, mail, placeholders");
        $this->line("  php nova mail:test <address>          sends a test mail with the settings from config/config.ini");
        $this->line("");
        $this->line("Example:");
        $this->line("  php nova make Product    → /product, table \"product\"");
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
        $placeholders = ['Ihr Unternehmen', 'Your Business', 'Muster GmbH', 'Max Mustermann', 'info@example.com', 'Musterstadt', 'Musterstraße 1'];
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

    //creates src/controller/<Name>.class.php, src/model/<Name>Model.class.php and src/view/<name>/show.phtml
    protected function make($args)
    {
        $name = null;
        $table = null;
        $withModel = true;

        foreach ($args as $arg) {
            if ($arg === '--no-model') {
                $withModel = false;
            } elseif (strpos($arg, '--table=') === 0) {
                $table = substr($arg, strlen('--table='));
            } elseif ($name === null && strpos($arg, '--') !== 0) {
                $name = $arg;
            } else {
                $this->line("Unknown option: $arg");
                return 1;
            }
        }

        if ($name === null) {
            $this->line("Missing name. Example: php nova make Product");
            return 1;
        }

        $class = ucfirst($name);             //the router does ucfirst() on the url too
        $model = $class . 'Model';
        $folder = strtolower($class);        //the controller looks for the view in the lowercase folder
        $url = '/' . lcfirst($class);
        if ($table === null) {
            //ProductCategory → product_category
            $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
        }

        //checks
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $class)) {
            $this->line("Invalid name \"$name\". Use letters, numbers and _ only, starting with a letter.");
            return 1;
        }
        if (in_array(strtolower($class), $this->reserved)) {
            $this->line("\"$class\" is a reserved word in PHP and can't be a class name. Try e.g. \"{$class}s\" with --table=" . strtolower($class));
            return 1;
        }
        if (in_array(strtolower($class), $this->framework) || in_array(strtolower($model), $this->framework)) {
            $this->line("\"$class\" is already used by the framework itself, pick another name.");
            return 1;
        }
        if (substr($class, -5) === 'Model') {
            $this->line("Leave out \"Model\" at the end, it gets added to the model automatically.");
            return 1;
        }
        if ($withModel && !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            $this->line("Invalid table name \"$table\".");
            return 1;
        }

        $files = [
            "src/controller/$class.class.php" => $this->controllerTemplate($class, $model, $folder, $url, $withModel),
            "src/view/$folder/show.phtml"     => $this->viewTemplate($class, $withModel),
        ];
        if ($withModel) {
            $files["src/model/$model.class.php"] = $this->modelTemplate($model, $table);
        }

        //the autoloader searches model/ before controller/, so a model with the controller's name would hide it
        $conflicts = ["src/model/$class.class.php", "src/controller/$class.class.php", "src/view/$folder/show.phtml"];
        if ($withModel) {
            $conflicts[] = "src/model/$model.class.php";
            $conflicts[] = "src/controller/$model.class.php";
        }
        foreach ($conflicts as $path) {
            if (file_exists($this->basepath . '/' . $path)) {
                $this->line("$path already exists, nothing was created.");
                return 1;
            }
        }

        foreach ($files as $path => $content) {
            $fullPath = $this->basepath . '/' . $path;
            if (!is_dir(dirname($fullPath)) && !mkdir(dirname($fullPath), 0775, true)) {
                $this->line("Could not create folder " . dirname($path));
                return 1;
            }
            if (file_put_contents($fullPath, $content) === false) {
                $this->line("Could not write $path");
                return 1;
            }
            $this->line("created  $path");
        }

        $this->line("");
        $this->line("Open $url in the browser" . ($withModel ? " (the table \"$table\" has to exist)." : "."));
        return 0;
    }

    protected function controllerTemplate($class, $model, $folder, $url, $withModel)
    {
        if (!$withModel) {
            return <<<PHP
<?php
class $class extends Controller
{
    public function showAction()
    {
        // $url → view /view/$folder/show.phtml
        // put data for the view into \$this->data['...']
    }
}

PHP;
        }

        return <<<PHP
<?php
class $class extends Controller
{
    // this page is PUBLIC: everybody can read the whole table. Remove it or put a login in front before going live if the data is private
    // columns that are never sent to the view
    protected \$hiddenColumns = ['password', 'password_hash', 'pass', 'pw', 'token'];

    public function showAction()
    {
        // $url        → all rows
        // $url?id=5   → only the row with id 5
        // Show HTML view: /view/$folder/show.phtml
        \$model = new $model();
        \$id = \$this->myrequest->getParam('id');

        if (\$id !== null) {
            \$row = \$model->find(\$id);
            \$rows = \$row ? [\$row] : [];
        } else {
            \$rows = \$model->all();
        }

        \$this->data['rows'] = array_map(
            fn(\$row) => array_diff_key(\$row->toArray(), array_flip(\$this->hiddenColumns)),
            \$rows
        );
    }
}

PHP;
    }

    protected function modelTemplate($model, $table)
    {
        return <<<PHP
<?php
class $model extends BaseModel
{
    protected \$table = '$table';
    // protected \$primaryKey = 'id';     // change if the key column isn't "id"
}

PHP;
    }

    protected function viewTemplate($class, $withModel)
    {
        if (!$withModel) {
            return <<<PHP
<section class="container section">
    <h1>$class</h1>
</section>

PHP;
        }

        //nowdoc (no $ replacing) because the view is full of php variables
        return str_replace('{{class}}', $class, <<<'PHP'
<section class="container section">
    <h1>{{class}}</h1>

    <?php if (empty($this->data['rows'])): ?>
        <p>Nothing found.</p>
    <?php else: ?>
        <table class="table">
            <tr>
                <?php foreach (array_keys($this->data['rows'][0]) as $column): ?>
                    <th><?= e($column) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($this->data['rows'] as $row): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <td><?= e($value) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</section>

PHP);
    }

    protected function line($text)
    {
        echo $text . PHP_EOL;
    }
}
