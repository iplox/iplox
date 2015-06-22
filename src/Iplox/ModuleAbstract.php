<?php

namespace Iplox;
use Iplox\Config;

abstract class ModuleAbstract {
	
	protected $config;
	protected $submodules;
	protected $router;

	public function __construct(Config $cfg){
		$this->config = $cfg;
	}
 
	public function __get($property){
		if($property === 'config'){
			return $this->config;
		}
	}
}