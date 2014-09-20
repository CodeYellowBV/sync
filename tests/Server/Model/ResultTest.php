<?php
use CodeYellow\Sync\Server\Model\Result;
use \Mockery as m;
class ServerModelResultTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if an invalidargument exception is thrown when totalrecords is not an integer
     * @expectedException \InvalidArgumentException
     */
    public function testTotalRecordsNoInteger()
    {
        new Result(array(), 'no integer');
    }

    /**
     * Test the asArray function and jsonDecode function
     */
    public function testAsArrayAndJsonDecode()
    {
        $data = array(['a' => 'test', 'b', 'test']);
        $totalRecords = 100;

        $result = new Result($data, $totalRecords);
        foreach ([$result->asArray(), json_decode($result->asJson(), true)] as $array) {
            $this->assertTrue(is_array($array));
            $this->assertEquals(count($data), $array['count']);
            $this->assertEquals($totalRecords - count($data), $array['remaining']);
            $this->assertEquals($data, $array['data']);
        }

    }
}