<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 * 
 * Copyright (C) 2025 Hamzah Mansor 
 **/
class Utils extends Controller
{

    public static function includeNavbar()
    {
        $navbarPath = BASEPATH . '/src/view/navbar.phtml';
        if (file_exists($navbarPath)) {
            require_once($navbarPath);
        } else {
            echo "No Navbar found!<br/>";
        }
    }

    public static function includeHead()
    {
        $navbarPath = BASEPATH . '/src/view/head.phtml';
        if (file_exists($navbarPath)) {
            require_once($navbarPath);
        } else {
            echo "No Head found!<br/>";
        }
    }

    public static function includeFooter()
    {
        $footerPath = BASEPATH . '/src/view/footer.phtml';
        if (file_exists($footerPath)) {
            require_once($footerPath);
        } else {
            echo "No Footer found!<br/>";
        }
    }
}
