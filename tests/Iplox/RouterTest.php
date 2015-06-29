<?php

use Iplox\Router;

class RouterTest extends PHPUnit_Framework_TestCase {

    public function testCheckRoute() {
        $r = new Router();

        $e1 = ['section-a'];
        $e2 = ['section-a', 'section-b'];
        $e3 = ['section-a', 'section-b', 'section-c/section-d'];

        $a1 = $r->checkRoute('/:a/:b', '/section-a/section-b');
        $a2 = $r->checkRoute('/:a/(:b)?', '/section-a');
        $a3 = $r->checkRoute('/:a/(:b)?', '/section-a/section-b');
        $a4 = $r->checkRoute('/:a/:b/*params', '/section-a/section-b/section-c/section-d');
        $a5 = $r->checkRoute('/:a/:b/(*params)?', '/section-a/section-b');
        $a6 = $r->checkRoute('/:a/:b/(*params)?', '/section-a/section-b/section-c/section-d');
        $a7 = $r->checkRoute('/:a/:b', '/section-a');
        $a8 = $r->checkRoute('/any', '/any/path');
        $a9 = $r->checkRoute('/this/is/(it)?', '/this/is');
        $a10 = $r->checkRoute('/this/is/(it)?', '/this/is/it');

        $this->assertEquals($e2, $a1, 'Expected an array of two (2) elements.');
        $this->assertEquals($e1, $a2,'The route with (:b)? means expects an optional. Failed when not passed.');
        $this->assertEquals($e2, $a3,'The route with (:b)? means expects an optional. Failed when passed.');

        $this->assertEquals($e3, $a4, 'Expected an array of three (3) elements.');
        $this->assertEquals($e2, $a5, 'The route with (*params)? means expects an optional. Failed when not passed.');
        $this->assertEquals($e3, $a6, 'A route with (*params)? means expects an optional. Failed when passed.');

        $this->assertFalse($a7, "The route '/:a/:b' matches wrongly the request '/section-a must'.");
        $this->assertEquals([], $a8, "The route '/any' does not match the request '/any/path'.");
        $this->assertEquals([], $a8, "The route '/any' does not match the request '/any/path'.");
        $this->assertEquals([], $a9, "The route '/this/is/(it)?' does not match the request '/this/is'.");
        $this->assertEquals(['it'], $a10, "The route '/this/is/(it)?' does not match the request '/this/is/it'.");
    }
}