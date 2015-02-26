<?php
use \Mockery as m;
use CodeYellow\Sync\Client\Model\Result;
class ClientModelResult extends PHPUnit_Framework_TestCase
{
    /**
     * Do a simple test with an empty response
     */
    public function testEmptyRequest()
    {
        $request = m::mock('\CodeYellow\Sync\Client\Model\Request');
        $result = new Result();

        $request->shouldReceive('setFrom');
        $request->shouldReceive('getOption')->andReturn(null);
        $request->shouldReceive('getData')->andReturn(['count' => 0, 'remaining' => 0, 'data' => []]);
        $result->bind($request);

        $this->assertEquals($result->valid(), false);
        $this->assertEquals($result->current(), null);
    }

    /**
     * Test a request with two responses
     */
    public function testTwoResult()
    {
        $request = m::mock('\CodeYellow\Sync\Client\Model\Request');
        $result = new Result();
        $data = [
                    ['id'=> 1, 'created_at' => 0],
                    ['id'=> 2, 'created_at' => 1]
                ];

        $request->shouldReceive('setFrom');
        $request->shouldReceive('getOption')->andReturn(null);

        $request->shouldReceive('getData')->andReturn(
            ['count' => 2, 'remaining' => 0, 'data' =>
                $data
            ]
        );
        $request->shouldReceive('getType')->andReturn(Result::TYPE_NEW);

        $result->bind($request);

        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data[0]);

        $result->next();
        
        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data[1]);

        $result->next();

        $this->assertEquals($result->valid(), false);
        $this->assertEquals($result->current(), null);

        $this->assertEquals($result->key(), 2);

        $result->rewind();

        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data[0]);
    }

    /**
     * Test a request with options
     *
     * @group blaat
     */
    public function testWithOptions()
    {
        $lastTime = 123123;
        $lastId = 1337;

        $request = m::mock('\CodeYellow\Sync\Client\Model\Request');
        $result = new Result();
        $data = [
            ['id'=> 1, 'created_at' => 0],
            ['id'=> 2, 'created_at' => 1]
        ];

        // Give request a startId of 1337.
        $request->shouldReceive('getOption')->with('startId', 1)->andReturn($lastId);
        $request->shouldReceive('getOption')->with('since', 0)->andReturn($lastTime);
        
        // Check that request is given 1337.
        $request->shouldReceive('setFrom')->with($lastTime, $lastId)->once();

        $request->shouldReceive('getData')->andReturn(
            ['count' => 2, 'remaining' => 0, 'data' =>
                $data
            ]
        );
        $request->shouldReceive('getType')->andReturn(Result::TYPE_NEW);

        $result->bind($request);

        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data[0]);

        $result->next();
        
        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data[1]);

        $result->next();

        $this->assertEquals($result->valid(), false);
        $this->assertEquals($result->current(), null);

        $this->assertEquals($result->key(), 2);

        $result->rewind();

        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data[0]);
    }

    /**
     * Test what happens if there are two request
     */
    public function testTwoRequest()
    {
        $request = m::mock('\CodeYellow\Sync\Client\Model\Request');
        $result = new Result();
        $data1 = [
                    ['id'=> 1, 'created_at' => 0],
                    ['id'=> 2, 'created_at' => 1]
                ];

        $data2 = [
            ['id'=> 3, 'created_at' => 1],
            ['id'=> 4, 'created_at' => 1]
        ];

        $request->shouldReceive('setFrom')->with(0, 0);
        $request->shouldReceive('setFrom')->with(1, 3);

        $request->shouldReceive('getData')->andReturn(
            ['count' => 2, 'remaining' => 2, 'data' =>
                $data1
            ]
        )->once();

        $request->shouldReceive('getData')->andReturn(
            ['count' => 2, 'remaining' => 0, 'data' =>
                $data2
            ]
        )->once();

        $request->shouldReceive('getOption')->andReturn(null);
        $request->shouldReceive('getType')->andReturn(Result::TYPE_NEW);

        $result->bind($request);


        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data1[0]);

        $result->next();
        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data1[1]);

        $result->next();
        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data2[0]);

        $result->next();
        $this->assertEquals($result->valid(), true);
        $this->assertEquals($result->current(), $data2[1]);

        $result->next();
        $this->assertEquals($result->valid(), false);
    }
}
