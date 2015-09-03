<?php

namespace Iplox\Mvc;
use Iplox\Config;
use Iplox\AbstractModule;
use Iplox\BaseModule;

class Module extends BaseModule
{
    public function __construct(Config $cfg, AbstractModule $parent = null, Array $injections = null)
    {
        parent::__construct($cfg, $parent, $injections);

        // Add the options related to this module.
        $cfg->addKnownOptions([
            'controllerNamespace' => 'Controllers',
            'controllerSuffix' => '',
            'alternativeMethodSuffix' => 'Action',
            'viewsDir' => 'views',
            'modelSuffix' => '',
            'entitySuffix' => '',
            'notFoundHandler' => 'Classes\\NotFound->index',
            'defaultController' => 'Index',
            'defaultMethod' => 'index',
            'moduleClassName' => __CLASS__
        ]);

        $cfg->refreshCache();

        //Determine if any of this routes actually exists as an object
        $this->router->addFilters(array(
            'controller' => function($name, $path) {
                $ns = $this->config->namespace . '\\' .
                    ucwords(preg_replace(['/^\//', '/\//'], ['', '\\'], $path)) .
                    $this->config->controllerNamespace . '\\';
                $class = $ns . ucwords($name) . $this->config->controllerSuffix;
                if (class_exists($class, true)){
                    return true;
                }
                return false;
            }
        ));

        // Filters for the MVC Controller functionality
        $this->router->appendRoutes([
            '/:controller/:method/{*params}?'=>  array($this, 'captureControllerMethod'),
            '/:controller/{*param}?' => array($this, 'captureController'),
            '/{*param}' => array($this, 'notFoundHandler'),
            '/' => array($this, 'captureDefault'),
        ]);
    }


    public function captureControllerMethod($controller, $method, $params='') {
        $controllerName =
            $this->config->namespace . '\\' .
            $this->config->controllerNamespace . '\\' .
            ucwords($controller) . $this->config->controllerSuffix;

        if(class_exists($controllerName)) {
            $inst = new $controllerName($this->config, $this, $this->injections);
            if(method_exists($inst, $method . ucwords($this->router->requestMethod))) {
                return call_user_func_array(array($inst, $method . ucwords($this->router->requestMethod)), preg_split('/\/{1}/', $params));
            } else if(method_exists($inst, $method . ucwords($this->config->alternativeMethodSuffix))){
                return call_user_func_array(array($inst, $method . ucwords($this->config->alternativeMethodSuffix)), preg_split('/\/{1}/', $params));
            } else {
                throw new \Exception("The method \"$method" .
                    ucwords($this->router->requestMethod)."\" or \"" .
                    $method . ucwords($this->config->get('alternativeMethodSuffix'))."\" was not found in the controller $controllerName.");
            }
        } else {
            throw new \Exception("The controller \"$controllerName\" was not found.");
        }
    }

    public function captureController($controller, $params='') {
        return $this->captureControllerMethod(
            $controller,
            $this->config->defaultMethod,
            $params
        );
    }

    public function captureDefault() {
        if($this->config->defaultController){
            return $this->captureControllerMethod(
                $this->config->defaultController,
                $this->config->defaultMethod
            );
        }
        throw new \Exception("Invalid ".
            $this->config->get('defaultController').$this->config->get('controllerSuffix').
            "->".$this->config->get('defaultMethod').$this->config->get('alternativeMethodSuffix').
            ". Verify that the values of these config options \"defaultController\" and \"defaultMethod\" are correct.");
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
}