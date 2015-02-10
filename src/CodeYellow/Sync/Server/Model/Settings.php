<?php
namespace CodeYellow\Sync\Server\Model;

class Settings implements CodeYellow\Sync\Type, SettingsInterface
{
    const FORMAT_TIMESTAMP = 'timestamp';
    const FORMAT_DATETIME = 'datetime';
    
    protected $timeFormat;
    protected $createdAtName;
    protected $updatedAtName;

    /**
     * Create a new settings class
     *
     * @param ENUM $timeFormat Timeformat either FORMAT_TIMESTAMP or FORMAT_DATETIME
     * @param string $createdAtName The column name for the created_at attribute
     * @param string $updatedAtName The column name for the updated_at attribute
     */
    public function __construct(
        $timeFormat = FORMAT_TIMESTAMP,
        $createdAtName = 'created_at',
        $updatedAtName = 'updated_at'
    ) {
        if (!in_array($timeFormat, [FORMAT_TIMESTAMP, FORMAT_DATETIME])) {
            throw new \InvalidArgumentException(
                'timeFormat must be either TIMESTAMP or DATETIME'
            );
        }
        $this->timeFormat = $timeFormat;
        $this->createdAtName = $createdAtName;
        $this->updatedAtName = $updatedAtName;
    }

    protected function formatTimeStamp($time)
    {
        if ($timeFormat == static::FORMAT_TIMESTAMP) {
            return $time;
        } else {
            return date('Y-m-d H:i:s', $time);
        }
    }

    /**
     * Get the column name that responds to $type
     * @param Enum $mode Mode that we are in. Either
     *        CodeYellow\Sync\Type\TYPE_NEW or
     *        CodeYellow\Sync\Type\TYPE_MODIFIEDs
     */
    protected function getColumnName($type)
    {
        switch ($type) {
            case TYPE_NEW:
                return $this->createdAtName;
            case TYPE_MODIFIED:
                return $this->updatedAtName;
        }

        throw new InvalidArgumentException(
            'Type ' . $type . ' does not exist'
        );
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
            $this->formatTimeStamp($time)
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
        $sortOn = $this->getColumnName();
        $time = $this->formatTimeStamp($time);

        $query->where(function ($query) use ($sortOn, $time, $startId) {
            $query->where($sortOn, '>', $time);
            $query->orWhere(
                function (
                    $query
                ) use (
                    $sortOn,
                    $time,
                    $startId
                ) {
                    $query->where($sortOn, '=', $time);
                    $query->where('id', '>=', $startId);
                }
            );
        });
    }
}
