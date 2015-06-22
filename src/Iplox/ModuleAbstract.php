<?php

namespace Iplox;

abstract class ModuleAbstract {
	
	protected $config;
	protected $submodules;
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
			return $this->submodules;
		}
	}

	public abstract function init($uri);
}