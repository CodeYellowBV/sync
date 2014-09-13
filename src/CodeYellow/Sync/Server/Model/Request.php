<?php
namespace CodeYellow\Sync\Server\Model;
use CodeYellow\Sync\Type;
use CodeYellow\Sync\Exception;

class Request implements Type
{
    private $type;
    private $limit;
    private $before;
    private $since;
    private $startId;

    private $result; // The result of this query

    /**
     * Create a sync. Sets ths json
     *
     * @throws Exception\Sync\MalformedJsonException If json is malformed
     * @throws Exception\Sync\wrongParameterException If json parameters do not meet the specification
     */
    public function __construct($json)
    {
        $this->readJson($json);
    }

    /**
     * Verifies the json. If json is ok -> sets the private vars
     */
    protected function readJson($json)
    {
        $request = json_decode($json);

        if (is_null($request)) {
            throw new Exception\MalformedJsonException();
        }

        foreach (['limit', 'before', 'since', 'startId'] as $option) {
            if ((!is_null($request->$option) && !is_int($request->$option))) {
                throw new Exception\WrongParameterException($option . ' should be an integer');
            }
        }

        // Read type
        if (!isset($request->type) || !in_array($request->type, [static::TYPE_NEW, static::TYPE_MODIFIED])) {
            throw new Exception\WrongParameterException('Wrong type');
        }

        $this->type = $request->type;
        $this->limit = $request->limit;
        $this->before = $request->before;
        $this->since = $request->since;
        $this->startId = $request->startId;
    }

    /**
     * Do a sync
     * 
     * Use $eloquent->getQuery() to get the query from eloquent
     * @param Illuminate\Database\Query\Builder $query The prepared query without
     * @param int $limit The limit for how many results may be exported. If null, use user limit
     */
    public function doSync(\Illuminate\Database\Query\Builder $query, $limit = null)
    {
        if (!is_int($limit) && !is_null($limit)) {
            throw new InvalidArgumentException('SyncRequest::doSync limit must be an integer');
        }

        // Check if we use created_at or updated at
        $sortOn = $this->type == static::TYPE_NEW ? 'created_at' : 'updated_at';
        $before = is_null($this->before) ? time() : min(time(), $this->before);

        // Disregard things from before now to ensure no results are lost
        $query->where($sortOn, '<', $before);

        // Unsynced result are where
        // (time > now || (time == now && id >= startId))
        $query->where(function ($query) use ($sortOn) {
            $query->where($sortOn, '>', $this->since);
            $query->orWhere(function ($query) use ($sortOn) {
                $query->where($sortOn, '=', $this->since);
                $query->where('id', '>=', $this->startId);
            });
        });

        // Check if a limit is set, if not, set limit to given limit
        $count = $query->aggregate('count');

        // Order correctly
        // must be done after aggregating
        $query->orderBy($sortOn, 'ASC');
        $query->orderBy('id', 'ASC');


        $limit == null && $limit = $this->limit;
        $query->limit(min($this->limit, $limit));
        $this->result = new Result($query->get(), $count);
        return $this->result;
    }

    /**
     * Getters
     */
    public function getType()
    {
        return $this->type;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getBefore()
    {
        return $this->before;
    }

    public function getSince()
    {
        return $this->since;
    }

    public function getStartId()
    {
        return $this->startId;
    }
}

