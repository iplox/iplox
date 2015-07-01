<?php

namespace Iplox\Restful;

use Iplox\BasicModule;

class Module extends BasicModule
{
    public function __construct($cfg)
    {

        parent::__construct($cfg);

        // Add the options related to this module.
        $cfg->addKnownOptions([
            'controllerNamespace' => 'Controllers',
            'controllerSuffix' => 'Controller',
            'alternativeMethodSuffix' => 'Action',
            'modelSuffix' => 'Model',
            'entitySuffix' => 'Entity',
            'notFoundHandler'=> 'notFoundHandler',
            'defaultGlobalHandler' => 'defaultGlobalHandler',
            'defaultController' => 'Index',
            'defaultMethod' => 'index',
            'moduleClassName' => 'Iplox\\Api\\Restful\\Module'
        ]);

        $cfg->refreshCache();

        // Filters for the Restful functionality
        $this->router->appendRoutes([
            '/:resource/(*uriExtra)?' =>  function($resourceName, $uriExtra = null) {
                if($class = $this->getResourceController($resourceName)){
                    return new $class($this->config, $uriExtra);
                }
                return false;
            },
        ]);
    }

    public function init($uri)
    {
        if(empty($uri)) {
            $uri = preg_replace('/\?(.*\=.*)*$/', '', $_SERVER['REQUEST_URI']) ;
            $uri = empty($uri) ? '/' : $uri;
        }
        $this->router->check($uri);
    }

    public function getResourceController($resourceName)
    {
        $class = $this->config->namespace . '\\' .
            $this->config->controllerNamespace . '\\' .
            ucwords($resourceName) . $this->config->controllerSuffix;
        if(class_exists($class)){
            return $class;
        }
        return false;
    }
}