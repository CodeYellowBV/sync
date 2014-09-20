<?php
use CodeYellow\Sync\Server\Model\Result;
use \Mockery as m;
class ResultTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if an invalidargument exception is thrown when totalrecords is not an integer
     * @expectedException \InvalidArgumentException
     */
    public function testTotalRecordsNoInteger()
    {
        new Result(array(), 'no integer');
    }
}