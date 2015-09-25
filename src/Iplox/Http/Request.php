<?php

namespace Iplox\Http;

class Request
{
    protected static $current;
    protected $uri;
    protected $hostname;
    protected $params;
    protected $method;

    public function __construct($uri = '/', Array $params = [], $hostname = null, $httpMethod = null, $body = null)
    {
        $this->uri = empty($uri) ? $this->removeQueryString($_SERVER['REQUEST_URI']) : $uri;
        $this->params = empty($params) ? $_REQUEST : $params;
        $this->hostname = empty($hostname) ? $_SERVER['SERVER_NAME'] : $hostname;
        $this->method = strtoupper(empty($httpMethod) ? $_SERVER['REQUEST_METHOD'] : $httpMethod);
        $this->body = !empty($body) && !is_array($body) ? $body :
            ($_POST ? $_POST : json_decode(file_get_contents('php://input'), true));
        if(empty($this->body)) {
            $this->body = [];
        }
    }

    public static function getCurrent()
    {
        if(null === static::$current){
            static::$current = new Request(
                null
            );
        }
        return static::$current;
    }

    public function removeQueryString($uri)
    {
        return preg_replace('/\?(.*\=.*)*$/', '', $uri);
    }

    public function __get($name)
    {
        if($name === 'method'){
            return $this->method;
        } else if($name === 'hostname') {
            return $this->hostname;
        } else if($name === 'uri') {
            return $this->uri;
        } else if ($name === 'params') {
            return $this->params;
        } else if($name === 'fullUrl') {
            return '//'.$this->hostname . $this->uri;
        }
    }

    /*****  HTTP Method Verification *****/
    //Devuelve true si el método solicitado es GET
    public function isGet() {
        if($this->method === 'GET') {
            return true;
        } else {
            return false;
        }
    }

    //Devuelve true si el metodo solicitado es POST
    public function isPost() {
        if($this->method === 'POST') {
            return true;
        } else {
            return false;
        }
    }

    //Devuelve tru si el metodo solicitado es PUT
    public function isPut() {
        if($this->method === 'PUT') {
            return true;
        } else {
            return false;
        }
    }

    //Devuelve tru si el metodo solicitado es DELETE
    public function isDelete() {
        if($this->method === 'DELETE') {
            return true;
        } else {
            return false;
        }
    }
}