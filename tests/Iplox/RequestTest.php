<?php

use Iplox\Request;

class RequestTest extends PHPUnit_Framework_TestCase {

    public function testIsGet(){
        $r = new Request();
        $_SERVER['REQUEST_METHOD']='GET';
        $this->assertTrue($r->isGet());
    }

    public function testIsPost(){
        $r = new Request();
        $_SERVER['REQUEST_METHOD']='POST';
        $this->assertTrue($r->isPost());
    }

    public function testIsDelete() {
        $r = new Request();
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $this->assertTrue($r->isDelete());
    }

    public function testIsPut() {
        $r = new Request();
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $this->assertTrue($r->isPut());
    }
}