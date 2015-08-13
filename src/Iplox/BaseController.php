<?php

namespace Iplox;
use Iplox\Http\Response;
use Iplox\Http\StatusCode;

class BaseController extends AbstractController
{
    protected $module;
    protected $config;

    public function __construct(Config $config, AbstractModule $module)
    {
        parent::__construct($config, $module);
        $this->config = $config;
        $this->module = $module;
    }

    public function respond($data, $contentType = null, $statusCode = StatusCode::OK)
    {
        $contentType = empty($contentType) ? $this->config->get('defaultContentType') : $contentType;
        return new Response($data, $contentType, $statusCode);
    }

    public function __get($name)
    {
        if($name === 'module'){
            return $this->module;
        } else if($name === 'config') {
            return $this->config;
        }
    }
}