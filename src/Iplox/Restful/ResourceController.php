<?php

namespace Iplox\Restful;
use Iplox\AbstractModule;
use Iplox\BaseController;
use Iplox\Config;
use Iplox\Http\Request;
use Iplox\Http\Response;

class ResourceController extends BaseController
{
    protected $handlerForMethods;
    public $response;

    public function __construct(Config $cfg, AbstractModule $module, $uri)
    {
        parent::__construct($cfg, $module);

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
            },
        ]);

        $req = Request::getCurrent();
        $handler = $this->handlerForMethods[strtolower($req->method)];

        // Filters for the Restful functionality
        $this->router->appendRoutes([
            '/:num/:resource' =>  array($this, '__routeToRelatedHandler'),
            '/:num' =>  array($this, $handler.'One'),
            '/:str' => array($this, '__routeToVerbHandler'),
            '/' =>  array($this, $handler),
        ]);

        $response = $this->router->check($uri);
        if($response instanceof Response){
            $this->response = $response;
        } else {
            $this->response = new Response(empty($response) ? [] : $response, $this->config->get('defaultContentType'));
        }
    }

    public function __routeToVerbHandler ($verb)
    {
        $verbFunc = [$this, $verb . ucwords(Request::getCurrent()->method)];
        if(is_callable($verbFunc)){
            return call_user_func($verbFunc);
        }

        $verbFunc[1] = $verb . $this->config->get('alternativeMethodSuffix');
        if(is_callable($verbFunc)){
            return call_user_func($verbFunc);
        }

        return false;
    }

    public function __routeToRelatedHandler($identifier, $related)
    {
        $prefix = $this->handlerForMethods[strtolower(Request::getCurrent()->method)];
        $handler = [$this, $prefix.$related];
        if(is_callable($handler)){
            return call_user_func($handler,  $identifier);
        }
        return [];
    }
}