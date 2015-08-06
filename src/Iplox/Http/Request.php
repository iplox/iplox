<?php

namespace Iplox\Http;

class Request
{
    protected static $current;
    protected $uri;
    protected $hostname;
    protected $params;

    public function __construct($uri = '/', $params = '', $hostname = null)
    {
        $this->uri = empty($uri) ? $this->removeQueryString($_SERVER['REQUEST_URI']) : $uri;
        $this->params = $params;
        $this->hostname = empty($hostname) ? $_SERVER['SERVER_NAME'] : $hostname;
    }

    public static function getCurrent()
    {
        if(null === static::$current){
            $uri = self::removeQueryString($_SERVER['REQUEST_URI']);
            static::$current = new Request(
                $uri,
                $_SERVER['QUERY_STRING']
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
            return $_SERVER['REQUEST_METHOD'];
        } else if($name === 'hostname') {
            return $this->hostname;
        } else if($name === 'uri') {
            return $this->uri;
        } else if ($name === 'params') {
            return $this->params;
        }
    }

    /*****  HTTP Method Verification *****/
    //Devuelve true si el método solicitado es GET
    public function isGet() {
        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        } else {
            return false;
        }
    }

    //Devuelve true si el metodo solicitado es POST
    public function isPost() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            return true;
        } else {
            return false;
        }
    }

    //Devuelve tru si el metodo solicitado es PUT
    public function isPut() {
        if($_SERVER['REQUEST_METHOD'] === 'PUT') {
            return true;
        } else {
            return false;
        }
    }

    //Devuelve tru si el metodo solicitado es DELETE
    public function isDelete() {
        if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            return true;
        } else {
            return false;
        }
    }
}