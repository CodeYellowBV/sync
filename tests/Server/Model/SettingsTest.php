<?php
use CodeYellow\Sync\Server\Model\Request;
use CodeYellow\Sync\Server\Model\Settings;

use \Mockery as m;

class SettingsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test if an exception is thrown when an invalid time format
     * is set
     * @expectedException \InvalidArgumentException
     */
    public function testConstructInvalidTimeFormat()
    {
        new Settings('random');
    }

    /**
     * Test if the Settings from unix time works correctly if a
     * timestamp settings is applied
     */
    public function testFromUnixTimeTimeStamp()
    {
        $time = time();

        $settingsTimestamp = new Settings(Settings::FORMAT_TIMESTAMP);
        $this->assertEquals($time, $settingsTimestamp->fromUnixTime($time));

    }

    /**
     * Test if the Settings from unix time works correctly if a
     * dateTime setting is applied
     */
    public function testFromUnixTimeDateTime()
    {
        $time = time();
        $settingsDateTime = new Settings(Settings::FORMAT_DATETIME);
        $this->assertEquals(date('Y-m-d H:i:s', $time), $settingsDateTime->fromUnixTime($time));
    }

    /**
     * Test if the toUnixTime works if FORMAT_DATETIME is applied
     */
    public function testToUnixTimeDateTime()
    {
        $time = date('Y-m-d H:i:s');
        $datetime = new DateTime($time);

        $settingsDateTime = new Settings(Settings::FORMAT_DATETIME);
        $this->assertEquals(strtotime($time), $settingsDateTime->toUnixTime($time));
        $this->assertEquals(strtotime($time), $settingsDateTime->toUnixTime($datetime));
    }

    /**
     * Test if the toUnixTime works if FORMAT_DATETIME is applied
     */
    public function testToUnixTimeTimeStamp()
    {
        $time = time();
        $settingsDateTime = new Settings(Settings::FORMAT_TIMESTAMP);
        $this->assertEquals($time, $settingsDateTime->toUnixTime($time));
    }

    /**
     * Test if the toUnixTime works if FORMAT_DATETIME is applied and
     * a null value is given (should return unll)
     */
    public function testToUnixTimeNull()
    {
        $settingsDateTime = new Settings(Settings::FORMAT_DATETIME);
        $this->assertNull($settingsDateTime->toUnixTime(null));
    }
}
