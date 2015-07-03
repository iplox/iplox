<?php

namespace Iplox\Restful;

use Iplox\BasicModule;

class Module extends BasicModule
{
    public function __construct($cfg)
    {

        parent::__construct($cfg);

        // Add the general options.
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
            'moduleClassName' => __CLASS__
        ]);

        // Options for mapping resources to controllers.
        $cfg->addKnownOptions('resourcesMapping', [
           'index' => 'Index'
        ]);

        $cfg->refreshCache();

        $this->router->addFilters([
            ':resource' => function($val) {
                $resources = $this->config->getSet('resourcesMapping');
                return is_array($resources) && array_key_exists($val, $resources) ? true : false;
            },
        ]);

        // Filters for the Restful functionality
        $this->router->appendRoutes([
            '/:resource/(*uriExtra)?' =>  function($resourceName, $uriExtra = '/') {
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
        $rn = $this->config->get($resourceName, 'resourcesMapping');
        $class = $this->config->namespace . '\\' .
            $this->config->controllerNamespace . '\\' .
            ucwords($rn) . $this->config->controllerSuffix;
        if(class_exists($class)){
            return $class;
        }
        return false;
    }
}