<?php

use Iplox\BasicModule;
use Iplox\Config;

class BasicModuleTest extends PHPUnit_Framework_TestCase {	
	
    public function testIfInstance(){
        $m = new BasicModule(new Config);
        $this->assertTrue($m instanceof BasicModule, "Is not getting instanced.");
    }

} 