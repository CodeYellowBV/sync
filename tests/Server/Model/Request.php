<?php
use CodeYellow\Sync\Server\Model\Request;
class RequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if a normal request does not trigger any exceptions
     */
    public function testRequestNoException()
    {
        $request = [
            'type' => Request::TYPE_NEW,
            'limit' => 100,
            'before' => null,
            'since' => 0,
            'startId' => 0
        ];

        $req = new Request(json_encode($request));

        $this->assertEquals($request['type'], $req->getType());
        $this->assertEquals($request['limit'], $req->getLimit());
        $this->assertEquals($request['before'], $req->getBefore());
        $this->assertEquals($request['since'], $req->getSince());
        $this->assertEquals($request['startId'], $req->getStartId());
    }

    /**
     * Tests if an exception is thrown when the json is malformed
     * @expectedException CodeYellow\Sync\Exception\MalformedJsonException
     */
    public function testMalformedJsonException()
    {
        new Request("no json");
    }

    public function wrongParameterExceptionProvider()
    {
        return array(
            [[
                'type' => Request::TYPE_NEW,
                'limit' => '1',
                'before' => null,
                'since' => 0,
                'startId' => 0
            ]],
            [[
                'type' => Request::TYPE_NEW,
                'limit' => 100,
                'before' => '1',
                'since' => 0,
                'startId' => 0
            ]],
            [[
                'type' => Request::TYPE_NEW,
                'limit' => 100,
                'before' => null,
                'since' => '1',
                'startId' => 0
            ]],
            [[
                'type' => Request::TYPE_NEW,
                'limit' => 100,
                'before' => null,
                'since' => 0,
                'startId' => true
            ]],
            [[
                'type' => 'nwoipgnwiognw',
                'limit' => 100,
                'before' => null,
                'since' => 0,
                'startId' => 0
            ]],
        );
    }

    /**
     * Tests if an unexpected type exception is thrown when the type is wrong
     * @expectedException CodeYellow\Sync\Exception\WrongParameterException
     * @dataProvider wrongParameterExceptionProvider
     */
    public function testWrongParameterException($request)
    {
        new Request(json_encode($request));
    }

    /**
     * Tests if an invalid argument exception is given if 
     * @expectedException InvalidArgumentException
     */
    public function testDoSyncInvalid()
    {
        $request = [
            'type' => Request::TYPE_NEW,
            'limit' => 100,
            'before' => null,
            'since' => 0,
            'startId' => 0
        ];
        $req = new Request(json_encode($request));
        $req->doSync((new \Illuminate\Database\Query\Builder));
    }
}