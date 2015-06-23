<?php

namespace Iplox;

abstract class ModuleAbstract {
	
	protected $config;
	protected $router;

	public function __construct(Config $cfg){
		$this->config = $cfg;
		$this->router = new Router();
	}
 
	public function __get($prop){
		if($prop === 'config'){
			return $this->config;
		} else if($prop === 'router') {
			return $this->router;
		} else if($prop === 'modules'){
			return $this->config->modules;
		}
	}

	public abstract function init($uri);
}