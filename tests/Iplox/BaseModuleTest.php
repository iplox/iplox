<?php

use Iplox\BaseModule;
use Iplox\Config;
use Iplox\Http\Request;

class BaseModuleTest extends PHPUnit_Framework_TestCase {
	
    public function testIfInstance(){
        $m = new BaseModule(new Config);
        $this->assertTrue($m instanceof BaseModule, "Is not getting instanced.");
    }

    public function testForMiddleWares()
    {
        $cfg = new Config([
            'routes' => [
                'get the/right/result' => ['before' => ['strModification'], 'handler' => function(){}],
            ]
        ]);
        $m = new BaseModule($cfg);
        $result = '';

        $stringMod= function($req, $res) use(&$result){
             $result = "The Right Result";
        };
        $m->setMiddleWare('strModification', $stringMod);
        $mw = $m->getMiddleWare('strModification');


        $req = new Request('the/right/result', 'the=value1&param=value2', 'iplox.org', 'GET');
        $m->init($req);

        $this->assertEquals($stringMod, $mw, "The middlware are not registering corretly. The functions must be the same.");
        $this->assertEquals('The Right Result', $result, 'The before middlewares are not getting executed.');
    }

} 