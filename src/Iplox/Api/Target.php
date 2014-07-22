<?php

    namespace Iplox\Api {
        use Iplox\Mvc\Controller;
        
        class Target extends Controller{
            protected $targetList;
            protected $resource;
            
            public function __construct(){
                $this->Router = new Router();
            }
            
            public function Add($route, $handler){
                
            }
            
            public function AddList($list){
                
            }
            
        }
        
    }
}
