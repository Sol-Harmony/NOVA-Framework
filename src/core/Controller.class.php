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

    public function __construct($classname, $request)
    {
        $this->classname = $classname;
        $this->myrequest = $request;
    }

    // Set to respond as JSON
    protected function asJson(array $data)
    {
        $this->responseFormat = 'json';
        $this->data = $data;
    }

    // Set custom view (e.g., method-specific)
    protected function setView($view)
    {
        $this->view = $view;
    }

    // Called automatically after controller method is done
    public function respond($method)
    {
        if ($this->responseFormat === 'json') {
            header('Content-Type: application/json');
            echo json_encode($this->data);
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

        Utils::includeHead();
        Utils::includeNavbar();
        include_once($viewFile);
        Utils::includeFooter();
    }
}
