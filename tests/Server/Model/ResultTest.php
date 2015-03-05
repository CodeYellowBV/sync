<?php
use CodeYellow\Sync\Server\Model\Result;
use CodeYellow\Sync\Server\Model\Settings;

use \Mockery as m;

class ServerModelResultTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if an invalidargument exception is thrown when totalrecords is not an integer
     * @expectedException \InvalidArgumentException
     */
    public function testTotalRecordsNoInteger()
    {
        new Result(
            array(),
            'no integer',
            new Settings(),
            null
        );
    }

    /**
     * Test the asArray function and jsonDecode function
     */
    public function testAsArrayAndJsonDecode()
    {
        $data = array(['a' => 'test', 'b', 'test']);
        $totalRecords = 100;

        $result = new Result(
            $data,
            $totalRecords,
            new Settings(),
            null
        );
        foreach ([$result->asArray(), json_decode($result->asJson(), true)] as $array) {
            $this->assertTrue(is_array($array));
            $this->assertEquals(count($data), $array['count']);
            $this->assertEquals($totalRecords - count($data), $array['remaining']);
            $this->assertEquals($data, $array['data']);
        }
    }

    /**
     * Test if the conversion to unix time works as intended
     */
    public function testAsArrayToUnixTime()
    {
        $createdAt = date('Y-m-d');
        $updatedAt = date('Y-m-d H:i:s');
        $data = array([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'test' => $createdAt
        ]);
        $result = new Result(
            $data,
            1,
            new Settings(),
            null
        );
        $data = $result->asArray()['data'][0];
        // Test should not be converted to datetime
        $this->assertEquals($createdAt, $data['test']);

        // Created at and updated at should be converted
        $this->assertEquals(strtotime($createdAt), $data['created_at']);
        $this->assertEquals(strtotime($updatedAt), $data['updated_at']);
    }

    public function testTransformer()
    {
        $settings = new Settings();
        $transformer = m::mock('CodeYellow\\Sync\\Server\\ModelTransformerInterface');
        $data = [
            'a' => [1, 2, 3],
            'b' => [4, 5, 6],
            'c' => [7, 8, 9]
        ];

        $transformer->shouldReceive('transform')->with($data);

        $result = new Result(
            $data,
            count($data),
            $settings,
            $transformer
        );

    }
}
