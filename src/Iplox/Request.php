<?php
/**
 * Created by IntelliJ IDEA.
 * User: jrszapata
 * Date: 6/29/15
 * Time: 3:31 PM
 */

namespace Iplox;


class Request
{
    protected $current;

    public function __construct ()
    {

    }

    public function getCurrent()
    {
        if(null === static::$current){
            static::$current = new Request();
        }

        return static::$current;
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