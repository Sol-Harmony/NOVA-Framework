<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/

//everything is set up in src/bootstrap.php: autoloader, config, error handling.
//errors are only shown in the browser when config/config.ini has "debug = 1" under [app]. Without that file (a live server) they never are

define("BASEPATH", __DIR__);
require BASEPATH . '/src/bootstrap.php';

try {
    Security::sendHeaders();                    //security headers for every response

    $request = new Request();                   //get the request class and pass it to router to work with it there
    new Router($request);                       //go to router with request

} catch (\Throwable $th) {
    Router::error(500, null, $th);
}
