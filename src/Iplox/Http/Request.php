<?php

namespace Iplox\Http;


class Request
{
    protected static $current;

    public static function getCurrent()
    {
        if(null === static::$current){
            static::$current = new Request();
        }

        return static::$current;
    }

    public function __get($name)
    {
        if($name === 'method'){
            return $_SERVER['REQUEST_METHOD'];
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