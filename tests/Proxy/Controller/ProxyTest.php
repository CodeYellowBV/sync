<?php
use CodeYellow\Sync\Proxy\Controller\Proxy;
use \Mockery as m;

class Syncer
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
        $sync = new Syncer;
        $guzzle = m::mock('\GuzzleHttp\Client');
        $app = m::mock('\Illuminate\Foundation\Application');
        $url = "example.org";
        $text = "lorem ipsum";


        $guzzle->shouldReceive('post')->with($url, m::on(function ($data) use ($guzzle, $text) {
            $this->assertEquals('', $data['body']);
            $guzzle->shouldReceive('getBody')->andReturn($text);
            return true;
        }))->andReturn($guzzle);


        $sync->setConfig(array('servers' => ['test' => ['url' => $url]]));
        $sync->setGuzzle($guzzle);
        $sync->setApp($app);

        $result = $sync->sync('test');
        $this->assertEquals($text, $result);
    }

    /**
     * Test if an abort is done with a 404 when the sync does not exist
     */
    public function testAbort()
    {
        $sync = m::mock('syncer')->makePartial();
        $app = m::mock('Illuminate\Foundation\Application');

        $sync->setApp($app);

        $sync->shouldReceive('getConfig')->andReturn(array('servers' => []));
        $app->shouldReceive('abort')->with(404);

        $sync->sync('test');
    }

    /**
     * Test app aborts on request fail
     */
    public function testAbortApp()
    {
        $sync = new Syncer;
        $guzzle = m::mock('\GuzzleHttp\Client');
        $app = m::mock('\Illuminate\Foundation\Application');
        $exception = m::mock('\GuzzleHttp\Exception\BadResponseException');
        $exceptionResponse = m::mock();
        $url = "example.org";
        $text = "lorem ipsum";

        $sync->setConfig(array('servers' => ['test' => ['url' => $url]]));
        $sync->setGuzzle($guzzle);
        $sync->setApp($app);

        $guzzle->shouldReceive('post')->andThrow($exception);
        $exception->shouldReceive('getResponse')->andReturn($exceptionResponse);
        $exceptionResponse->shouldReceive('getStatusCode')->andReturn('404');

        $app->shouldReceive('abort');

        $result = $sync->sync('test');
    }
}
