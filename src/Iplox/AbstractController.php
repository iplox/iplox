<?php

namespace Iplox;

abstract class AbstractController
{
    public $router;
    protected $response;

    public function __construct(){
        //Each controller might have a Router object property.
        $this->router = new Router();
    }

    public function __get($name)
    {
        if($name === 'response') {
            return $this->response;
        }
    }

    public abstract function respond($data, $contentType = null, $statusCode = StatusCode::OK);

}