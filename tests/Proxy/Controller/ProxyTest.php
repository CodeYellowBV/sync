<?php
use CodeYellow\Sync\Proxy\Controller\Proxy;
use \Mockery as m;

class Sync
{
    use proxy;
}

class ProxyControllerProxyTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if a sync is succesfull
     */
    public function testSync()
    {
        Config::$getClosure = function ($name) {
            return [$name];
        };

        dd(Config::get('test'));
        $sync = new Sync('<');
        $sync->sync('test');
    }
}
