<?php

use Iplox\BaseModule;
use Iplox\Config;

class BaseModuleTest extends PHPUnit_Framework_TestCase {
	
    public function testIfInstance(){
        $m = new BaseModule(new Config);
        $this->assertTrue($m instanceof BaseModule, "Is not getting instanced.");
    }

} 