<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class dbConnect
{
    private static $pdo = null;     //one shared connection for the whole request

    //connect to server with pdo
    //the credentials are in the [database] section of config/config.ini. a website without a database doesn't need them

    public function connect()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dbData = Config::get('database');
        if (!is_array($dbData)) {
            throw new RuntimeException("No database data available. Copy config/config.example.ini to config/config.ini and fill in the [database] section.");
        }
        foreach (['servername', 'username', 'password', 'database'] as $key) {
            if (!isset($dbData[$key])) {
                throw new RuntimeException("Missing '$key' in the [database] section of config/config.ini");
            }
        }

        try {
            $dsn = "mysql:host=" . $dbData['servername'] . ";dbname=" . $dbData['database'] . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $dbData['username'], $dbData['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $th) {
            //rethrow without the original exception, its stack trace contains the password
            throw new RuntimeException("OOPS! Connection Failed: " . $th->getMessage());
        }

        return self::$pdo = $pdo;
    }

    //wraps a table or column name in backticks (so reserved words like `default` work)
    //and rejects anything that isn't a plain name, so user input can't inject sql through keys
    protected function quoteName($name)
    {
        $parts = explode('.', $name);   //allows database.table
        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $part)) {
                throw new InvalidArgumentException("Invalid table or column name: $name");
            }
        }
        return '`' . implode('`.`', $parts) . '`';
    }
}
