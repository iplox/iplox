<?php

namespace Iplox\Restful;

use Iplox\AbstractModule;
use Iplox\BaseModule;
use Iplox\Config;
use Iplox\Http\Request;
use Iplox\Http\Response;

class Module extends BaseModule
{
    public function __construct(Config $cfg, AbstractModule $mod, Array $injections = null)
    {
        parent::__construct($cfg, $mod, $injections);

        // Add the general options.
        $cfg->addKnownOptions([
            'controllerNamespace' => 'Controllers',
            'controllerSuffix' => '',
            'alternativeMethodSuffix' => 'Action',
            'modelSuffix' => '',
            'entitySuffix' => '',
            'notFoundHandler'=> 'Classes\\NotFound->index',
            'defaultController' => 'Controllers\\Index',
            'defaultMethod' => 'index',
            'moduleClassName' => __CLASS__,
            'contentType' => 'application/json'
        ]);

        // Options for mapping resources to controllers.
        $cfg->addKnownOptions('resourceMappings', [
           'index' => 'Index'
        ]);

        $cfg->refreshCache();

        $this->router->addFilters([
            'resource' => function($val) {
                $resources = $this->config->getSet('resourceMappings');
                return is_array($resources) && array_key_exists($val, $resources) ? true : false;
            },
        ]);

        // Filters for the Restful functionality
        $this->router->appendRoutes([
            '/:resource/(*params)?' =>  function($resourceName, $params = '/') {
                if($class = $this->getResourceController($resourceName)){
                    $c = new $class($this->config, $this, $params);
                    return $c->response;
                }
                return false;
            },
            '/*params'=> function($params)  {
                return false; // Not found.
            },
            '/' => function() {
                //Check if the global handler method exists
                return $this->callGlobalIfDefined();
            }
        ]);
    }

    public function init($uri = null)
    {
        $req = new Request($uri);
        $this->baseUrl = ($this->parent and $this->parent->baseUrl) ?
            $this->parent->baseUrl . $this->config->get('route') :
            $this->config->get('route');

        $response = $this->router->check($uri);

        if($this->config->get('return') === true) {
            return $response;
        }

        // If not response was provided, call the not found handler.
        if ($response === false) {
            $response = $this->notFoundHandler($req->uri);
        }

        // If the response isn't a Response instance, then wrap it appropriately.
        if(! ($response instanceof Response)) {
            return new Response(empty($response) ? [] : $response, $this->config->get('contentType'));
        }

        // Set header(), echo() the data, exit().
        $response->end();
    }

    public function getResourceController($resourceName)
    {
        $rn = $this->config->get($resourceName, 'resourceMappings');
        $class = $this->config->namespace . '\\' .
            $this->config->controllerNamespace . '\\' .
            ucwords($rn) . $this->config->controllerSuffix;
        if(class_exists($class)){
            return $class;
        }
        return false;
    }

    public function notFoundHandler($params)
    {
        $handler = $this->config->get('notFoundHandler');
        if (is_callable($handler)) {
            return call_user_func($handler, $params);
        } elseif(is_string($handler) && preg_match('/^[\w\\\]*->\w*$/', $handler) > 0) {
            $parts = preg_split('/\->/', $handler);
            $className = $this->config->get('namespace').'\\'.$parts[0].$this->config->get('controllerSuffix');
            $methodName = $parts[1].$this->config->get('alternativeMethodSuffix');
            if(class_exists($className) and is_callable([
                    $inst = new $className($this->config, $this, $params),
                    $methodName
                ])){
                return call_user_func([$inst, $methodName]);
            }
        }
        throw new \Exception("A \"notFoundHandler\" config option was not provided or doesn't represent a callable function.");
    }

    public function callGlobalIfDefined()
    {
        $className = $this->config->get('namespace').'\\'.
            $this->config->get('defaultController') . $this->config->get('controllerSuffix');
        $methodName = $this->config->get('defaultMethod').$this->config->get('alternativeMethodSuffix');
        if(class_exists($className) and is_callable([
                $inst = new $className($this->config, $this),
                $methodName
            ])){
            return call_user_func([$inst, $methodName]);
        }

        return false;
    }
}