<?php
use CodeYellow\Sync\Server\Model\Request;
use \Mockery as m;
class RequestTest extends PHPUnit_Framework_TestCase
{
    static $database;

    public function getQuery()
    {
        return m::mock('\Illuminate\Database\Query\Builder');
    }
    
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
            'limit' => 0,
            'before' => 0,
            'since' => 0,
            'startId' => 0
        ];
        $req = new Request(json_encode($request));

        $req->doSync($this->getQuery(), 'test');
    }

    /**
     * Test a normal request
     */
    public function testNormalRequest()
    {
        $count = 42;
        $request = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => time() - 10,
            'since' => time() - 40,
            'startId' => 5
        ];

        $query = $this->getQuery();
        $query->shouldReceive('where')->with('created_at', '<', $request['before']);
        $query->shouldReceive('where')->with(m::on(function ($closure) use ($request) {
            $query2 = $this->getQuery();
            $query2->shouldReceive('where')->with('created_at', '>', $request['since']);
            $query2->shouldReceive('orWhere')->with(m::on(function ($closure2) use ($request) {
                $query3 = $this->getQuery();
                $query3->shouldReceive('where')->with('created_at', '=', $request['since']);
                $query3->shouldReceive('where')->with('id', '>=', $request['startId']);

                $closure2($query3);
                return true;
            }));
            $closure($query2);
            return true;
        }));
        $query->shouldReceive('aggregate')->with('count')->andReturn($count);
        $query->shouldReceive('orderBy')->with('created_at', 'ASC');
        $query->shouldReceive('orderBy')->with('id', 'ASC');
        $query->shouldReceive('limit')->with($request['limit']);

        $query->shouldReceive('get')->andReturn(array('test'));

        $req = new Request(json_encode($request));
        $result = $req->doSync($query);
        $this->assertInstanceOf('CodeYellow\Sync\Server\Model\Result', $result);
    }
}