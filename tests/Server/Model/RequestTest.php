<?php
use CodeYellow\Sync\Server\Model\Request;
use CodeYellow\Sync\Server\Model\Settings;
use \Mockery as m;
class ServerModelRequestTest extends PHPUnit_Framework_TestCase
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

        $req->doSync($this->getQuery(), new Settings(), 'test');
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
        $result = $req->doSync($query, new Settings(
            Settings::FORMAT_TIMESTAMP
        ));
        $this->assertInstanceOf('CodeYellow\Sync\Server\Model\Result', $result);
    }

    /**
     * Test if updated_at is selected if the request has set updated_at
     */
    public function testUpdatedAt()
    {
        $request = [
            'type' => Request::TYPE_MODIFIED,
            'limit' => 10,
            'before' => time() - 10,
            'since' => time() - 40,
            'startId' => 5
        ];

        $query = $this->getQuery();
        $query->shouldReceive('where')->with('updated_at', '<', $request['before']);
        $query->shouldReceive('where')->with(m::on(function ($closure) {
            return $closure instanceof Closure;
        }));
        $query->shouldReceive('aggregate')->with('count')->andReturn(1);
        $query->shouldReceive('orderBy')->with('updated_at', 'ASC');
        $query->shouldReceive('orderBy')->with('id', 'ASC');
        $query->shouldReceive('get')->andReturn(array('test'));
        $query->shouldReceive('limit');

        $req = new Request(json_encode($request));
        $result = $req->doSync($query, new Settings(
            Settings::FORMAT_TIMESTAMP
        ));
        $this->assertInstanceOf('CodeYellow\Sync\Server\Model\Result', $result);
    }

    /**
     * Test if before is set to current time if it is set to null
     */
    public function testBeforeAndSinceIsNull()
    {
        $request = [
            'type' => Request::TYPE_MODIFIED,
            'limit' => 10,
            'before' => null,
            'since' => null,
            'startId' => 5
        ];
        $timeAfter = time();

        $query = $this->getQuery();
        $query->shouldReceive('where')->with('updated_at', '<', m::on(function ($time) use ($timeAfter) {
            $this->assertLessThanOrEqual($time, $timeAfter);
            return true;
        }));


        $query->shouldReceive('aggregate')->with('count')->andReturn(1);
        $query->shouldReceive('orderBy')->with('updated_at', 'ASC');
        $query->shouldReceive('orderBy')->with('id', 'ASC');
        $query->shouldReceive('get')->andReturn(array('test'));
        $query->shouldReceive('limit');

        $req = new Request(json_encode($request));
        $result = $req->doSync($query, new Settings(
            Settings::FORMAT_TIMESTAMP
        ));
        $this->assertInstanceOf('CodeYellow\Sync\Server\Model\Result', $result);
    }

    /**
     * Test no limits
     */
    public function testNoLimits()
    {
        $request = [
            'type' => Request::TYPE_MODIFIED,
            'limit' => null,
            'before' => time(),
            'since' => null,
            'startId' => 5
        ];

        $query = $this->getQuery();
        $query->shouldReceive('where')->with('updated_at', '<', $request['before']);


        $query->shouldReceive('aggregate')->with('count')->andReturn(1);
        $query->shouldReceive('orderBy')->with('updated_at', 'ASC');
        $query->shouldReceive('orderBy')->with('id', 'ASC');
        $query->shouldReceive('get')->andReturn(array('test'));

        $req = new Request(json_encode($request));
        $result = $req->doSync($query, new Settings(
            Settings::FORMAT_TIMESTAMP
        ));
        $this->assertInstanceOf('CodeYellow\Sync\Server\Model\Result', $result);
    }

    /**
     * Test if limit passed to the function overrides user limit
     */
    public function testLimitOverride()
    {
        $clientLimit = 1;
        $request = [
            'type' => Request::TYPE_MODIFIED,
            'limit' => 10,
            'before' => time(),
            'since' => null,
            'startId' => 5
        ];

        $this->assertGreaterThan($clientLimit, $request['limit']);

        $query = $this->getQuery();
        $query->shouldReceive('where')->with('updated_at', '<', $request['before']);


        $query->shouldReceive('aggregate')->with('count')->andReturn(1);
        $query->shouldReceive('orderBy')->with('updated_at', 'ASC');
        $query->shouldReceive('orderBy')->with('id', 'ASC');
        $query->shouldReceive('get')->andReturn(array('test'));
        $query->shouldReceive('limit')->with($clientLimit);

        $req = new Request(json_encode($request));
        $result = $req->doSync($query, new Settings(
            Settings::FORMAT_TIMESTAMP
        ), $clientLimit);
        $this->assertInstanceOf('CodeYellow\Sync\Server\Model\Result', $result);
    }
}