<?php

namespace Iplox;
use Iplox\Http\Response;
use Iplox\Http\StatusCode;

class Controller {
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

    public function respond($data, $contentType = null, $statusCode = StatusCode::OK)
    {
        $contentType = empty($contentType) ? $this->config->get('defaultContentType') : $contentType;
        return new Response($data, $contentType, $statusCode);
    }

}