<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Controller
{
    protected $classname;
    protected $myrequest;
    protected $responseFormat = 'html'; // default is HTML
    protected $view = null;
    protected $data = [];

    protected $status = 200;            // http status of the page
    protected $title = null;            // <title> of the page, the name of the website is added. null = name and tagline of the website
    protected $description = null;      // meta description for search engines, null = the default from config/site.php
    protected $noindex = false;         // true = search engines should not list this page

    // http methods this controller answers. everything else gets "405 Method Not Allowed"
    protected $allowedMethods = ['GET', 'HEAD', 'POST'];
    // true = every request that changes something (POST, ...) needs the csrf token of the form (see Csrf::field()).
    // only switch off for something that is called by other servers, like a webhook
    protected $csrf = true;

    public function __construct($classname, $request)
    {
        $this->classname = $classname;
        $this->myrequest = $request;
    }

    // used by the Router
    public function allowedMethods()
    {
        return $this->allowedMethods;
    }

    public function needsCsrf()
    {
        return $this->csrf;
    }

    // Set to respond as JSON
    protected function asJson(array $data, $status = 200)
    {
        $this->responseFormat = 'json';
        $this->data = $data;
        $this->status = $status;
    }

    // Set to respond with plain text or XML (robots.txt, sitemap.xml)
    protected function asText($body, $contentType = 'text/plain; charset=utf-8')
    {
        $this->responseFormat = 'raw';
        $this->data = ['body' => (string) $body, 'type' => $contentType];
    }

    // Set custom view (e.g., method-specific)
    protected function setView($view)
    {
        $this->view = $view;
    }

    // view in the language of the website: 'impressum/show' loads impressum/show.de.phtml, or show.en.phtml if there is none in that language
    protected function localizedView($name)
    {
        $lang = Lang::code();
        $this->view = file_exists(BASEPATH . '/src/view/' . $name . '.' . $lang . '.phtml') ? $name . '.' . $lang : $name . '.en';
    }

    // stops with an error page, e.g. $this->abort(404)
    protected function abort($code, $message = null)
    {
        Router::error($code, $message);
        exit;
    }

    // for actions that only make sense with a form submit: /contact/send
    protected function requirePost()
    {
        if (!$this->myrequest->isSubmit()) {
            header('Allow: POST');
            $this->abort(405);
        }
    }

    // sends the visitor to another page of this website. after handling a form always redirect (instead of showing a page),
    // otherwise a reload of the browser sends the form again
    protected function redirect($to, $status = 303)
    {
        // only paths of this website: "/contact". no "https://other.site", no "//other.site"
        if (!is_string($to) || $to === '' || $to[0] !== '/' || strpos($to, '//') === 0 || preg_match('/[\\\\\x00-\x1F]/', $to)) {
            $to = '/';
        }
        header('Location: ' . $to, true, $status);
        exit;
    }

    // <title> of the page
    public function pageTitle()
    {
        $site = (string) Config::get('site.name', '');
        if ($this->title) {
            return $site === '' ? $this->title : $this->title . ' – ' . $site;
        }
        $tagline = Config::text('site.tagline');
        return $tagline === '' ? $site : $site . ' – ' . $tagline;
    }

    public function pageDescription()
    {
        return (string) ($this->description ?: Config::text('site.description'));
    }

    // true for pages search engines should skip: error pages, and every page while 'noindex' => true in config/site.php
    public function isNoindex()
    {
        return $this->noindex || Seo::noindex();
    }

    // path of the "official" address of this page, for <link rel="canonical">: /contact and /kontakt both give /kontakt
    public function canonicalPath()
    {
        $path = '/' . trim($this->myrequest->getPath(), '/');
        $parts = array_values(array_filter(explode('/', $path), 'strlen'));
        if (!$parts || ($parts === ['home'])) {
            return '/';
        }
        return count($parts) === 1 ? Utils::url($this->classname, null, false) : $path;
    }

    // includes a part of the layout or a part of a page from /src/view, e.g. 'head'
    public function partial($name)
    {
        if (!preg_match('#^[A-Za-z0-9_/.-]+$#', $name) || strpos($name, '..') !== false) {
            throw new InvalidArgumentException("Invalid view name: $name");
        }
        include BASEPATH . '/src/view/' . $name . '.phtml';
    }

    // error page in the layout of the website, used by Router::error()
    public function renderError($code, $message, $detail = null)
    {
        $this->status = $code;
        $this->title = t('error.title', ['code' => $code]);
        $this->noindex = true;
        $this->view = 'error';
        $this->data = ['code' => $code, 'message' => $message, 'detail' => $detail];
        $this->respond('showAction');
    }

    // Called automatically after controller method is done
    public function respond($method)
    {
        if ($this->responseFormat === 'json') {
            // encode first: if that fails nothing was sent yet and the Router can still answer with an error
            $json = json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            http_response_code($this->status);
            header('Content-Type: application/json; charset=utf-8');
            echo $json;
            exit;
        }

        if ($this->responseFormat === 'raw') {
            http_response_code($this->status);
            header('Content-Type: ' . $this->data['type']);
            echo $this->data['body'];
            exit;
        }

        // fallback to default view (controller/method.phtml)
        $viewFile = BASEPATH . '/src/view/' . strtolower($this->classname) . '/' . str_replace('Action', '', $method) . '.phtml';
        if ($this->view) {
            $viewFile = BASEPATH . '/src/view/' . $this->view . '.phtml';
        }

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: $viewFile");
        }

        // the page is collected first and sent at the end. so a view can still start the session (cookie = header)
        // and a view that fails halfway doesn't leave half a page in front of the error message
        ob_start();
        try {
            $this->partial('head');
            $this->partial('navbar');
            echo '<main id="main">';
            include $viewFile;
            echo '</main>';
            $this->partial('footer');
        } catch (\Throwable $th) {
            ob_end_clean();
            throw $th;
        }

        http_response_code($this->status);
        header('Content-Type: text/html; charset=utf-8');
        ob_end_flush();
    }
}
