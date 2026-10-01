<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 * 
 * Copyright (C) 2025 Hamzah Mansor 
 **/

//true while developing: errors and exception messages are shown in the browser
//set to false on the live website: visitors only see a generic error, details go to the php error log
define("DEBUG", true);

ini_set('display_errors', DEBUG ? 1 : 0);
ini_set('display_startup_errors', DEBUG ? 1 : 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include('src/core/Autoloader.php'); //include the autoloader class to load other classes automatically

define("BASEPATH", __DIR__);

try {
    $autoloader = new Autoloader(BASEPATH);      //autoloader class to load other classes automatically
    $autoloader->trigger();                     //spl autoload register to do the funtion inside(findfile) each time a class is called

    $request = new Request();                   //get the request class and pass it to router to work with it there
    new Router($request);                       //go to router with request

} catch (\Throwable $th) {
    Router::error(500, "Something went wrong.", $th);
}
