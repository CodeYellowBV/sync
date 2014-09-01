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
}