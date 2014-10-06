<?php
use \Mockery as m;
use CodeYellow\Sync\Client\Model\Request;
class ClientModelRequest extends PHPUnit_Framework_TestCase
{
    /**
     * Simple test if things that are created are safed correctly
     */
    public function testCreate()
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69
        ];
        $request = new Request('example.org', $options);
        $array = $request->asArray();
        $this->assertEquals($options, $array);

        $json = json_decode($request->asJson(), true);
        $this->assertEquals($options, $json);

        $this->assertEquals($options['type'], $request->getType());
    }

    /** 
     * Test if an exception is thrown if an invalidargument is set
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69,
            'invalid' => 101
        ];
        new Request('example.org', $options);

    }

    /** 
     * Test if an InvalidArgumentException is thrown if a wrong type is set
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentExceptionWrongType()
    {
        $options = [
            'type' => 9999,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69,
            'invalid' => 101
        ];
        new Request('example.org', $options);
    }

    /** 
     * Test if an InvalidArgumentException is thrown if the set values are not integer
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentExceptionIfNotInteger()
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => '10',
            'before' => 1337,
            'since' => 42,
            'startId' => 69,
            'invalid' => 101
        ];
        new Request('example.org', $options);
    }

    /**
     * Tests if the getData function works properly
     */
    public function testGetData()
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69
        ];
        $url = 'http://example.org';

        $guzzleMock = m::mock('\GuzzleHttp\Client');
        $resultMock = m::mock('stupid');

        $guzzleMock->shouldReceive('post')->with($url, m::on(function ($body) use ($options) {
            $this->assertEquals(json_encode($options), $body['body']);

            return true;
        }))->andReturn($resultMock);

        $resultMock->shouldReceive('json');

        $request = new Request($url, $options);
        $request->setGuzzle($guzzleMock);

        $request->getData();
    }

    /**
     * Test if the set from function works properly
     */
    public function testSetFrom()
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69
        ];
        $request = new Request('example.org', $options);
        $request->setFrom(1337, 314);
        $array = $request->asArray();
        $this->assertEquals(1337, $array['since']);
        $this->assertEquals(314, $array['startId']);
    }

    /**
     * Test a Simple Fetch data workrs 
     */
    public function testSimpleFetchData()
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69
        ];
        $result = [
            ['id' => 1, 'deleted' => false],
            ['id' => 2, 'deleted' => false]
        ];


        $resultMock = m::mock('CodeYellow\Sync\Client\Model\ResultInterface');
        $modelInterface = m::mock('CodeYellow\Sync\Client\Model\modelInterface');

        $request = new Request('example.org', $options);
        $request->setResultInstance($resultMock);

        $resultMock->shouldReceive('bind')->with($request)->andReturn($result);
        $modelInterface->shouldReceive('itemExists')->with($result[0]['id'])->andReturn(false);
        $modelInterface->shouldReceive('createItem')->with($result[0]);

        $modelInterface->shouldReceive('itemExists')->with($result[1]['id'])->andReturn(false);
        $modelInterface->shouldReceive('createItem')->with($result[1]);

        $request->fetchData($modelInterface);
    }

    public function fetchDataProvider()
    {
        return [
            [1, true, true, 'deleteItem'],
            [2, true, false, 'updateItem'],
            [3, true, null, 'updateItem'],
            [4, false, false, 'createItem'],
            [5, false, null, 'createItem'],
            [6, false, true, null]
        ];
    }
    /**
     * Do a more thorough test of testSimpleFetchDAta
     *
     * @param int $id Id of the user to be added/updated
     * @param bool $itemExists Does the item exist in the database already?
     * @param bool $deleted Should the user be deleted. If null, deleted will not be set
     * @param string $method. The method that should be called on the model 
     * @dataProvider fetchDataProvider
     */
    public function testFetchData($id, $itemExists, $deleted, $method)
    {
        $options = [
            'type' => Request::TYPE_NEW,
            'limit' => 10,
            'before' => 1337,
            'since' => 42,
            'startId' => 69
        ];

        $resultMock = m::mock('CodeYellow\Sync\Client\Model\ResultInterface');
        $modelInterface = m::mock('CodeYellow\Sync\Client\Model\modelInterface');

        $request = new Request('example.org', $options);
        $request->setResultInstance($resultMock);

        $result['id'] = $id;
        !is_null($deleted) && $result['deleted'] = $deleted;

        $resultMock->shouldReceive('bind')->with($request)->andReturn([$result]);
        $modelInterface->shouldReceive('itemExists')->with($id)->andReturn($itemExists);
        if ($method != null) {
            $modelInterface->shouldReceive($method)->with($method == 'deleteItem' ? $result['id'] : $result);
        }

        $request->fetchData($modelInterface);
    }
}
