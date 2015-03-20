<?php
/**
 * Created by IntelliJ IDEA.
 * User: jrszapata
 * Date: 3/19/15
 * Time: 10:13 PM
 */
use Iplox\Mvc\AppController;

class AppControllerTest extends PHPUnit_Framework_TestCase {

    public function testIsStarted() {
        $_SERVER['REQUEST_URI'] = '/christmas-dinner/mom-and-dad';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $app = AppController::init();
        $this->assertTrue($app->isStarted(), 'AppController::isStarted() not responding appropriately.');
        $app2 = AppController::init();
        $this->assertTrue($app2->isStarted(), 'AppController::isStarted() not responding appropriately.');
    }
}