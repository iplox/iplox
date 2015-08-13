<?php

namespace Iplox\Restful;

use Iplox\AbstractModule;
use Iplox\BaseModule;
use Iplox\Config;

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
            'defaultController' => 'Index',
            'defaultMethod' => 'index',
            'moduleClassName' => __CLASS__,
            'defaultContentType' => 'application/json'
        ]);

        // Options for mapping resources to controllers.
        $cfg->addKnownOptions('resourceMappings', [
           'index' => 'Index'
        ]);

        $cfg->refreshCache();

        $this->router->addFilters([
            ':resource' => function($val) {
                $resources = $this->config->getSet('resourceMappings');
                return is_array($resources) && array_key_exists($val, $resources) ? true : false;
            },
        ]);

        $mod = $this;
        // Filters for the Restful functionality
        $this->router->appendRoutes([
            '/:resource/(*uriExtra)?' =>  function($resourceName, $extraUri = '/') use(&$mod) {
                if($class = $this->getResourceController($resourceName)){
                    $c = new $class($this->config, $mod, $extraUri);
                    return $c->response;
                }
                return false;
            },
        ]);
    }

    public function init($uri = null)
    {
        if(empty($uri)) {
            $uri = preg_replace('/\?(.*\=.*)*$/', '', $_SERVER['REQUEST_URI']) ;
            $uri = empty($uri) ? '/' : $uri;
        }
        $response = $this->router->check($uri);
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
}