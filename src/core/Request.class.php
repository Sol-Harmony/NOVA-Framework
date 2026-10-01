<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Request
{
    protected $data = [];
    protected $json = null;

    public function getParam($name, $default = null) //function to check if exists a get or post with the parameter name exists, and then applies the method toget
    {
        if (array_key_exists($name, $_GET)) {
            return mb_convert_encoding($_GET[$name], 'UTF-8');
        } elseif (array_key_exists($name, $_POST)) {
            return mb_convert_encoding($_POST[$name], 'UTF-8');
        } elseif (array_key_exists($name, $this->getJson())) {
            return $this->getJson()[$name];
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return $default;
    }

    //like getParam(), but always a trimmed text ('' when it is missing or not a text, e.g. ?name[]=x)
    public function getText($name)
    {
        $value = $this->getParam($name, '');
        return is_string($value) ? trim($value) : '';
    }

    //the body of a request with "Content-Type: application/json" as array (empty array if there is none or it is broken)
    public function getJson()
    {
        if ($this->json === null) {
            $this->json = [];
            if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                $decoded = json_decode((string) file_get_contents('php://input', false, null, 0, 1048576), true);
                if (is_array($decoded)) {
                    $this->json = $decoded;
                }
            }
        }
        return $this->json;
    }

    public function getUrl()
    { //function to get the url whenever you want
        return $_SERVER['REQUEST_URI'];
    }

    public function getPath()
    { //the url without the query string (?id=5), used by the router. read the query values with getParam()
        return explode('?', $_SERVER['REQUEST_URI'], 2)[0];
    }

    public function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isSubmit()    //function that returns true when a form was submitted with method="post"
    {
        return $this->method() === "POST";
    }

    //GET and HEAD only read something. every other method changes something and needs a csrf token
    public function isSafeMethod()
    {
        return in_array($this->method(), ['GET', 'HEAD'], true);
    }

    //address of the visitor. behind a proxy (Cloudflare, load balancer) every visitor has the proxy's address, so tell in
    //config/config.ini which header holds the real one: [security] proxy_header = "HTTP_CF_CONNECTING_IP". never set it without a proxy, visitors could fake it
    public function getIp()
    {
        $header = Config::get('security.proxy_header');
        if (is_string($header) && $header !== '' && !empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}
