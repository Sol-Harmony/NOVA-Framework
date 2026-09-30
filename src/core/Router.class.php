<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Router
{
    public function __construct($request)
    {
        //the session stays open until the end of the request, so $_SESSION writes are saved.
        //call session_write_close() in a controller if a slow request shouldn't block other requests of the same user
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $url = $request->getPath();      //get the url without the query string from request class
        $url = trim($url, '/');
        if ($url === '') {
            $url = "home/show";
        }

        $matches =  explode('/', $url); //save in array matches every element from URL that's splitted with (/)

        $class = ucfirst($matches[0]);           //first word from array (z.b home), gets the class with that name
        $classname = ucfirst($matches[0]);       //classname gonna be used in controller to "show" the phtml page
        if (!empty($matches[1])) {
            $method = lcfirst($matches[1]) . 'Action';      //second word form url is the called method name
        } else {
            $method = "showAction";
        }

        //only plain names reach the autoloader, and only controllers can be created from the url
        $validName = '/^[A-Za-z][A-Za-z0-9_]*$/';
        if (!preg_match($validName, $class) || !class_exists($class) || !is_subclass_of($class, 'Controller')) {
            self::error(404, "Invalid page request.", "Controller $class does not exist.");
            return;
        }
        if (!preg_match($validName, $method)) {
            self::error(404, "Invalid page request.", "Method $method does not exist in class $class.");
            return;
        }

        try {
            $myclass = new $class($classname, $request);

            if (!is_callable([$myclass, $method])) {
                self::error(404, "Invalid page request.", "Method $method does not exist in class $class.");
                return;
            }

            $myclass->$method();
            $myclass->respond($method); // decides whether to show view or return JSON
        } catch (\Throwable $th) {
            self::error(500, "Something went wrong.", $th);
        }
    }

    //sends the status code and a short message. details (exception message) are only shown when DEBUG is on,
    //exceptions are always written to the php error log
    public static function error($code, $message, $detail = null)
    {
        if ($detail instanceof \Throwable) {
            error_log((string) $detail);
            $detail = $detail->getMessage();
        }
        if (!headers_sent()) {
            http_response_code($code);
        }
        echo "Error $code: " . htmlspecialchars($message) . "<br/>";
        if (DEBUG && $detail) {
            echo htmlspecialchars($detail);
        }
    }
}
