<?php

use Iplox\Config;

class ConfigTest extends PHPUnit_Framework_TestCase {	

    public function testIfStoreAndRetrive(){
    	$c = new Config(['a'=>'valueA']);
    	$this->assertEquals('valueA', $c->get('a'), 'Not storing config data on instancing.');
    	
    	$c->set('b', 'valueB');
    	$this->assertEquals('valueB', $c->get('b'), "Isn\'t storing or retrieving simple values.");

    	$exp = ['c' => 'valueC', 'd'=> 'valueD'];
    	$c->set($exp);
    	$this->assertTrue($exp == $c->get(['c', 'd']), "It isn't storing or retrieving arrays.");
    	
    	$c->env = 'Staging';
		$this->assertEquals('Staging', $c->get('env'), "It isn't storing using dynamic properties.");
    }

    public function testIfReadingSet() {

    }
}