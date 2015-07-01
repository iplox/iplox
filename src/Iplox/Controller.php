<?php

namespace Iplox;

class Controller {
    public $router;
    public function __construct(){
        //Each controller might have a Router object property.
        $this->router = new Router();
    }

}