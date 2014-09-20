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

        $sync = m::mock('sync')->makePartial();
        $guzzle = m::mock('\GuzzleHttp\Client');
        $url = "example.org";
        $text = "lorem ipsum";

        $sync->shouldReceive('getConfig')->andReturn(array('servers' => ['test' => ['url' => $url]]));
        $sync->shouldReceive('getGuzzle')->andReturn($guzzle);

        $guzzle->shouldReceive('post')->with($url, m::on(function ($data) use ($guzzle, $text) {
            $this->assertEquals('', $data['body']);
            $guzzle->shouldReceive('getBody')->andReturn($text);
            return true;
        }))->andReturn($guzzle);

        $result = $sync->sync('test');
        $this->assertEquals($text, $result);
    }

    /**
     * Test if an abort is done with a 404 when the sync does not exist
     */
    public function testAbort()
    {
        $sync = m::mock('sync')->makePartial();
        $app = m::mock('app');

        $sync->shouldReceive('getConfig')->andReturn(array('servers' => []));
        $sync->shouldReceive('getApp')->andReturn($app);
        $app->shouldReceive('abort')->with(404);
        $sync->sync('test');
    }
}
