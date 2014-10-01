<?php
use CodeYellow\Sync\Proxy\Controller\Proxy;
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
        $request = new Request('example.org', $options);

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
        $request = new Request('example.org', $options);
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
        $request = new Request('example.org', $options);
    }
}
