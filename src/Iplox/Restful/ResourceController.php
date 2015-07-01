<?php

namespace Iplox\Restful;
use Iplox\Controller;
use Iplox\Request;
use Iplox\Router;

class ResourceController extends Controller
{
    protected $handlerForMethods;

    public function __construct($cfg, $uri)
    {
        parent::__construct();

        $this->config = $cfg;
        $this->handlerForMethods = [
            'get' => 'get',
            'post' => 'create',
            'put'=> 'update',
            'delete' => 'delete'
        ];

        $this->router->addFilters([
            ':num' => function($val){
                return is_numeric($val) ? true : false;
            }
        ]);

        $req = Request::getCurrent();
        $handler = $this->handlerForMethods[strtolower($req->method)];

        // Filters for the Restful functionality
        $this->router->appendRoutes([
            '/:num/:relation' =>  array($this, '__routeToRelatedHandler'),
            '/:num' =>  array($this, $handler.'One'),
            '/:str' => array($this, '__routeToVerbHandler'),
            '/' =>  array($this, $handler),
        ]);

        $this->router->check($uri);
    }


    public function __routeToVerbHandler ($verb)
    {
        $verbFunc = $verb.$this->config->get('alternativeMethodSuffix');
        $prefix = $this->handlerForMethods[strtolower(Request::getCurrent()->method)];
        if(is_callable($verbFunc)) {
            return call_user_func([$this, $verbFunc]);
        } else if(is_callable([$this, $prefix])){
            return call_user_func([$this, $prefix]);
        } else {
            return false;
        }
    }

    public function __routeToRelatedHandler($identifier, $related)
    {
        $prefix = $this->handlerForMethods[Request::getCurrent()->method];
        $handler = [$this, $prefix.$related];
        if(is_callable($handler)){
            call_user_func($handler,  $identifier);
        }
    }

    public function ok($data, $status = HttpStatus::OK) {
        if(!empty($data)){
            header('Content-type: application/json');
            echo json_encode($data);
        }
    }

    public function error($error, $status) {

    }

    public function create()
    {

    }

    public function get()
    {
        return [];
    }

    public function getOne()
    {

    }

    public function update()
    {

    }

    public function updateOne()
    {

    }

    public function delete()
    {

    }

    public function deleteOne()
    {

    }

    public function doInAnyOtherCase()
    {

    }
}