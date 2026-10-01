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
        //the session is NOT started here. it starts when a page needs it (Session::start(), Csrf::field()),
        //so visitors that only read pages don't get a cookie

        $url = $request->getPath();      //get the url without the query string from request class
        $url = trim($url, '/');
        if ($url === '') {
            $url = "home/show";
        }

        $matches =  explode('/', $url); //save in array matches every element from URL that's splitted with (/)

        //pretty urls from config/site.php: 'routes' => ['kontakt' => 'contact']. /kontakt/send then runs contact/send
        $routes = Config::get('site.routes', []);
        //files search engines ask for. they have to be at exactly these addresses
        $routes = array_merge(['robots.txt' => 'robots', 'sitemap.xml' => 'sitemap'], is_array($routes) ? $routes : []);
        $alias = strtolower($matches[0]);
        if (is_array($routes) && isset($routes[$alias]) && is_string($routes[$alias])) {
            $matches = array_merge(explode('/', trim($routes[$alias], '/')), array_slice($matches, 1));
        }

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
            self::error(404, null, "Controller $class does not exist.");
            return;
        }
        if (!preg_match($validName, $method)) {
            self::error(404, null, "Method $method does not exist in class $class.");
            return;
        }

        try {
            $myclass = new $class($classname, $request);

            if (!is_callable([$myclass, $method])) {
                self::error(404, null, "Method $method does not exist in class $class.");
                return;
            }

            //PUT, DELETE and so on only work where a controller allows them
            if (!in_array($request->method(), $myclass->allowedMethods(), true)) {
                header('Allow: ' . implode(', ', $myclass->allowedMethods()));
                self::error(405, null, $request->method() . " is not allowed for $class.");
                return;
            }

            //everything that changes something needs the secret token of the form
            if (!$request->isSafeMethod() && $myclass->needsCsrf() && !Csrf::validate()) {
                self::error(403, t('error.csrf'), "CSRF token missing or wrong.");
                return;
            }

            $myclass->$method();
            $myclass->respond($method); // decides whether to show view or return JSON
        } catch (\Throwable $th) {
            self::error(500, null, $th);
        }
    }

    //sends the status code and a short message, as an error page in the layout of the website (or as JSON for javascript requests).
    //details (exception message) are only shown when DEBUG is on, exceptions are always written to the error log
    public static function error($code, $message = null, $detail = null)
    {
        if ($detail instanceof \Throwable) {
            error_log((string) $detail);
            $detail = $detail->getMessage();
        }
        if ($message === null) {
            $message = self::defaultMessage($code);
        }
        $detail = (DEBUG && $detail) ? (string) $detail : null;

        while (ob_get_level() > 0) {    //drop what was already collected, so no half page is in front of the error
            @ob_end_clean();
        }

        if (Utils::wantsJson()) {
            if (!headers_sent()) {
                http_response_code($code);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['error' => $message, 'code' => $code] + ($detail ? ['detail' => $detail] : []), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            return;
        }

        try {
            (new Controller('error', new Request()))->renderError($code, $message, $detail);
        } catch (\Throwable $th) {
            //the layout itself is broken: plain text is better than nothing
            error_log((string) $th);
            if (!headers_sent()) {
                http_response_code($code);
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error ' . (int) $code . '</title></head><body>';
            echo '<h1>Error ' . (int) $code . '</h1><p>' . htmlspecialchars($message) . '</p>';
            if ($detail) {
                echo '<pre>' . htmlspecialchars($detail) . '</pre>';
            }
            echo '</body></html>';
        }
    }

    private static function defaultMessage($code)
    {
        try {
            $text = t('error.' . $code);
            return $text === 'error.' . $code ? 'Error' : $text;
        } catch (\Throwable $th) {
            return 'Error';
        }
    }
}
