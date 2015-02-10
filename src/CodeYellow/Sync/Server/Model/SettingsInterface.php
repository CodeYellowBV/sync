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
}
