<?php
namespace CodeYellow\Sync\Server\Model;

use \CodeYellow\Sync\Type;

class Settings implements Type, SettingsInterface
{
    const FORMAT_TIMESTAMP = 'timestamp';
    const FORMAT_DATETIME = 'datetime';
    
    protected $timeFormat;
    protected $createdAtName;
    protected $updatedAtName;
    protected $deletedAtName;

    /**
     * Create a new settings class
     *
     * @param ENUM $timeFormat Timeformat either FORMAT_TIMESTAMP or FORMAT_DATETIME
     * @param string $createdAtName The column name for the created_at attribute
     * @param string $updatedAtName The column name for the updated_at attribute
     */
    public function __construct(
        $timeFormat = null,
        $createdAtName = 'created_at',
        $updatedAtName = 'updated_at',
        $deletedAtName = 'deleted_at'
    ) {
        if (is_null($timeFormat)) {
            $timeFormat = static::FORMAT_DATETIME;
        }

        if (!in_array($timeFormat, [static::FORMAT_TIMESTAMP, static::FORMAT_DATETIME])) {
            throw new \InvalidArgumentException(
                'timeFormat must be either' . static::FORMAT_TIMESTAMP .
                ' or ' . static::FORMAT_DATETIME . ' but is'
                . $timeFormat
            );
        }

        $this->timeFormat = $timeFormat;
        $this->createdAtName = $createdAtName;
        $this->updatedAtName = $updatedAtName;
        $this->deletedAtName = $deletedAtName;
    }

    /**
     * Format timestamp from unix timestamp to the timestamp
     * that is used in the model.
     *
     * @param int $time UnixTime
     * @return mixed TimeStamp
     */
    public function fromUnixTime($time)
    {
        if ($this->timeFormat == static::FORMAT_TIMESTAMP) {
            return $time;
        }
        
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * Format timestamp from locak timestamp to the unix timestamp
     * that is used in the model.
     *
     * @param mixed $time Timestamp
     * @return int Unix timestamp
     */
    public function toUnixTime($time)
    {
        if ($this->timeFormat == static::FORMAT_TIMESTAMP) {
            return $time;
        }

        if ($time instanceof \DateTime) {
            return $time->getTimeStamp();
        }

        if (is_string($time)) {
            return strtotime($time);
        }

        return null;
    }


    /**
     * Get the column name that responds to $type
     * @param Enum $mode Mode that we are in. Either
     *        CodeYellow\Sync\Type\TYPE_NEW or
     *        CodeYellow\Sync\Type\TYPE_MODIFIEDs
     */
    protected function getColumnName($type)
    {
        if ($type == static::TYPE_NEW) {
            return $this->createdAtName;
        }
        return $this->updatedAtName;

    }

    /**
     * Set the before attribute of the query. I.e. all
     * returned results need to be before time $time
     *
     * @param \Illuminate\Database\Query\Builder $query
     *        Query to set before time for
     * @param Enum $mode Mode that we are in. Either
     *        CodeYellow\Sync\Type\TYPE_NEW or
     *        CodeYellow\Sync\Type\TYPE_MODIFIEDs
     * @param int $time Unix timestamp for the before time
     */
    public function setBefore(
        \Illuminate\Database\Query\Builder $query,
        $mode,
        $time
    ) {
        $query->where(
            $this->getColumnName($mode),
            '<',
            $this->fromUnixTime($time)
        );
    }

    /**
     * Set the before since attribute of the query.
     * Unsynced result are where
     * (time > now || (time == now && id >= startId))
     *
     * @param \Illuminate\Database\Query\Builder $query
     *        Query to set before time for
     * @param Enum $mode Mode that we are in. Either
     *        CodeYellow\Sync\Type\TYPE_NEW or
     *        CodeYellow\Sync\Type\TYPE_MODIFIEDs
     * @param int $time Unix timestamp for the since time
     * @param int $id The id from where to start synching
     */
    public function setSince(
        \Illuminate\Database\Query\Builder $query,
        $mode,
        $time,
        $startId
    ) {
        $sortOn = $this->getColumnName($mode);
        $time = $this->fromUnixTime($time);

        $query->where(function($query) use ($sortOn, $time, $startId) {
            $query->where($sortOn, '>', $time);
            $query->orWhere(function($query) use ($sortOn, $time, $startId) {
                $query->where($sortOn, '=', $time);
                $query->where('id', '>=', $startId);
            });
        });

    }

    /**
     * Order the results of a query
     *
     * @param \Illuminate\Database\Query\Builder $query
     *        Query to set before time for
     * @param Enum $mode Mode that we are in. Either
     *        CodeYellow\Sync\Type\TYPE_NEW or
     *        CodeYellow\Sync\Type\TYPE_MODIFIEDs
     */
    public function order(
        \Illuminate\Database\Query\Builder $query,
        $mode
    ) {
        $query->orderBy($this->getColumnName($mode), 'ASC');
        $query->orderBy('id', 'ASC');
    }

    /**
     * Return all the fields that should be dealt with as a time field
     * @return array String Names of fields that are datetime
     */
    public function getTimeFields()
    {
        return [
            $this->updatedAtName,
            $this->createdAtName,
            $this->deletedAtName
        ];
    }

    /**
     * Returns an array representation of the settings object
     * This is communicated to the client, so that the client
     * also knows the current settings
     *
     * @return array Array representation of the settings
     */
    public function asArray()
    {
        // N.b. no timestamp information, because that
        // is not needed for the client
        return [
            'createdAtName' => $this->createdAtName,
            'updatedAtName' => $this->updatedAtName,
            'deletedAtName' => $this->deletedAtName
        ];
    }
}
