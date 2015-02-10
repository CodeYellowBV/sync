<?php
namespace CodeYellow\Sync\Server\Model;

interface SettingsInterface
{
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
    );

    /**
     * Format timestamp from unix timestamp to the timestamp
     * that is used in the model.
     *
     * @param int $time UnixTime
     * @return mixed TimeStamp
     */
    public function fromUnixTime($time);

    /**
     * Format timestamp from locak timestamp to the unix timestamp
     * that is used in the model.
     *
     * @param mixed $time Timestamp
     * @return int Unix timestamp
     */
    public function toUnixTime($time);

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
    );

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
    );

    /**
     * Return all the fields that should be dealt with as a time field
     * @return array String Names of fields that are datetime
     */
    public function getTimeFields();
}
