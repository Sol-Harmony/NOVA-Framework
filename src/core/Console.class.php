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
        $this->line("");
        $this->line("Example:");
        $this->line("  php nova make Product    → /product, table \"product\"");
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
<div style="margin-top:0; color:white;">
    <h1>$class</h1>
</div>

PHP;
        }

        //nowdoc (no $ replacing) because the view is full of php variables
        return str_replace('{{class}}', $class, <<<'PHP'
<div style="margin-top:0; color:white;">
    <h1>{{class}}</h1>

    <?php if (empty($this->data['rows'])): ?>
        <p>Nothing found.</p>
    <?php else: ?>
        <table style="margin: 0 auto; border-collapse: collapse; background: rgba(0, 0, 0, 0.35);">
            <tr>
                <?php foreach (array_keys($this->data['rows'][0]) as $column): ?>
                    <th style="padding: 6px 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.4);"><?= htmlspecialchars($column) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($this->data['rows'] as $row): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <td style="padding: 6px 12px;"><?= htmlspecialchars((string) $value) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

PHP);
    }

    protected function line($text)
    {
        echo $text . PHP_EOL;
    }
}
