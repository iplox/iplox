<?php

namespace Iplox;

abstract class AbstractModule {
	
	protected $config;

	public function __construct(Config $cfg){
		$cfg->addKnownOptions([
			'name' => 'noname',
			'namespace' => '',
			'directory' => '',
			'configDir' => 'configs',
			'env' => 'development',
			'configFilesSuffix' => '',
			'route' => ''
		]);

		// In submodules this is usually necessary.
		$cfg->refreshCache();

		list($dir, $configDir, $suffix, $env) =
			array_values($cfg->get(['directory', 'configDir', 'configFilesSuffix', 'env']));

		// Absolute path to the config directory
		if(preg_match('/^\//', $configDir) === 0){
			$configDir = $dir . DIRECTORY_SEPARATOR . $configDir;
		}
		$configDir = realpath($configDir) . DIRECTORY_SEPARATOR;


		// General config file for the $optionSet.
		$cfg->addFile(function($setName) use (&$cfg, &$configDir, &$suffix){
			return $configDir . $setName . $suffix . '.php';
		}, '*');

		// Config file for the $optionSet for the current enviroment.
		$cfg->addFile(function($setName) use (&$cfg, &$configDir, &$suffix, &$env){
			return $configDir . $env . DIRECTORY_SEPARATOR . $setName . $suffix . '.php';
		}, '*');

		// The 'Default' optionSet was already cached.
		// So, we need to refresh the object so it loads the options from the config files.
		$cfg->refreshCache();

		$this->config = $cfg;
	}
 
	public function __get($name){
		if($name === 'config'){
			return $this->config;
		}
	}

	public abstract function init($uri);
}