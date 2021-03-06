<?php
/**
 * Request class
 *
 * PHP Version 5.4
 *
 * @category Sync
 * @package  CodeYellow\Sync\
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT Licence http://opensource.org/licenses/MIT
 * @link     https://github.com/codeyellowbv/sync
 */

namespace CodeYellow\Sync\Server\Model;

use CodeYellow\Sync\Type;
use CodeYellow\Sync\Exception;
use CodeYellow\Sync\Server\Model\SettingsInterface;
use CodeYellow\Sync\Logger\Logger;
use Psr\Log\LogLevel;

/**
 * Server\Model\Request class, handles request that are received
 *
 * @category Sync
 * @package  CodeYellow\Sync\
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT Licence http://opensource.org/licenses/MIT
 * @link     https://github.com/codeyellowbv/sync
 */
class Request implements Type
{
    use Logger;

    private $type;
    private $limit;
    private $before;
    private $since;
    private $startId;

    private $result; // The result of this query
    private $json; // Raw request. Stored for debugging

    /**
     * Create a sync. Sets ths json
     *
     * @param string $json Json format of the request
     *
     * @throws Exception\Sync\MalformedJsonException If json is malformed
     * @throws Exception\Sync\wrongParameterException If json parameters do
     *         not meet the specification
     */
    public function __construct($json)
    {
        $this->json = $json;
        $this->readJson($json);
    }

    /**
     * Verifies the json. If json is ok -> sets the private vars
     *
     * @param string $json Json format of the request
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
     * @param SettingsInterface $settings Settings for this request
     * @param TransformInterface $transformer Transformer to be applied to the dataset before
     * the data is returned to the user
     * @param int $limit The limit for how many results may be exported. If null, use user limit
     */
    public function doSync(
        \Illuminate\Database\Query\Builder $query,
        SettingsInterface $settings,
        $limit = null,
        TransformInterface $transformer = null
    ) {
        if (!is_int($limit) && !is_null($limit)) {
            throw new \InvalidArgumentException('SyncRequest::doSync limit must be an integer');
        }
        $this->log(LogLevel::INFO, 'Start sync with request ' . $this->json);

        // Set an upperbound for the timestamp, to make sure that edits that
        // are made this second are not lost
        $before = is_null($this->before) ? time() : min(time(), $this->before);
        $settings->setBefore($query, $this->type, $before);

        // Unsynced result are where
        // (time > now || (time == now && id >= startId))
        if ($this->since != null) {
            $settings->setSince($query, $this->type, $this->since, $this->startId);
        }

        // Cloning because aggregate undoes select
        $countQuery = clone $query;

        // Check if a limit is set, if not, set limit to given limit
        $count = $countQuery->aggregate('count');

        // Order correctly
        // must be done after aggregating
        $settings->order($query, $this->type);

        // Check if there is some sort of limit set
        if ($limit != null || $this->limit != null) {
            $limit == null && $limit = $this->limit;
            $query->limit(min($this->limit, $limit));
        }
        $this->result = new Result($query->get()->toArray(), $count, $settings, $transformer);
        $this->log(LogLevel::DEBUG, 'Result ' .$this->result->asJson());
        return $this->result;
    }

    /**
     * Get the type of the request
     *
     * @return Type Type of the request
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the user provided limit
     *
     * @return int User provided limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get end time of request
     *
     * @return int End time of the request
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * Get the start date of the request
     *
     * @return int Start time of this request
     */
    public function getSince()
    {
        return $this->since;
    }

    /**
     * Get the start id
     *
     * @return int Start id
     */
    public function getStartId()
    {
        return $this->startId;
    }
}
