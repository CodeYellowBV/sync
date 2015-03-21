<?php
namespace CodeYellow\Sync\Client\Model;

use CodeYellow\Sync\Type;
use CodeYellow\Sync\Logger\Logger;

class Request implements Type
{
    use Logger;

    protected $options = ['type', 'limit', 'before', 'since','startId'];
    protected $url;
    
    protected $type;
    protected $limit;
    protected $before;
    protected $since;
    protected $startId;

    protected $result;
    protected $settings; // Settings as set by the server

    // Dependency injections
    protected $guzzleInstance;
    protected $resultInstance;



    /**
     * Return a new guzzle client
     * @param \GuzzleHttp\Client $client
     */
    public function setGuzzle(\GuzzleHttp\Client $client)
    {
        $this->guzzleInstance = $client;
    }

    /**
     * Return a new guzzle client
     */
    public function getGuzzle()
    {
        return $this->guzzleInstance;
    }

    /**
     * Set a result Instance
     * @param ResultInterface $result
     */
    public function setResultInstance(ResultInterface $result)
    {
        $this->resultInstance = $result;
    }

    /**
     * Returns a result instance
     * @return ResultInterface ResultInstance
     */
    public function getResultInstance()
    {
        return $this->resultInstance;
    }



    /**
     * Construct a new request
     * @param array $options Array of the options to be set.
     */
    public function __construct($url, array $options)
    {
        $this->setGuzzle(new \GuzzleHttp\Client());
        $this->setResultInstance(new Result());
        $this->url = $url;
        foreach ($options as $key => $val) {
            $this->setOption($key, $val);
        }
    }

    /**
     * Set an option for the request
     * @param string $name The name of the option
     * @param mixed $val The new value for the option
     * @throws Exception If a value does not exists
     */
    private function setOption($name, $val)
    {
        if (!in_array($name, $this->options) || $name == 'url') {
            throw new \InvalidArgumentException('Option with name ' . $name . ' does not exist');
        }
        $this->$name = $val;

        switch ($name) {
            case 'type':
                if (!in_array($val, [static::TYPE_NEW, static::TYPE_MODIFIED])) {
                    throw new \InvalidArgumentException(
                        'Unexpected type ' . $val .
                        ' Chose between ' . static::TYPE_MODIFIED . ' or ' . static::TYPE_NEW
                    );
                }
                break;
            default:
                if (!is_int($val) && ! is_null($val)) {
                    throw new \InvalidArgumentException(
                        'Option  ' . $name . ' should be an integer \'' .
                        $val . '\' is not an integer'
                    );
                }
        }
    }

    public function getOption($name, $default = null)
    {
        return $this->$name !== null ? $this->$name : $default;
    }

    /**
     * Returns an array of the options in this request
     * @returns array This object
     */
    public function asArray()
    {
        return [
            'type' => $this->type,
            'limit' => isset($this->limit) ? $this->limit : null,
            'before' => isset($this->before) ? $this->before : null,
            'since' => isset($this->since) ? $this->since : 0,
            'startId' => isset($this->startId) ? $this->startId : 0
        ];
    }

    /**
     * Returns a json string from the options
     */
    public function asJson()
    {
        return json_encode($this->asArray());
    }

    /**
     * Returns if an item of the result is deleted
     */
    private function isDeleted($item)
    {
        // If the server did not set a deletedAtName, stuff
        // can not be deleted
        if (is_null($this->settings['deletedAtName'])) {
            return false;
        }

        // Else check if deleted at is set
        return (
            isset($item[$this->settings['deletedAtName']])
            && (bool) $item[$this->settings['deletedAtName']]
        );
                
    }

    /**
     * Fetch more data
     * @param ModelInterface $model The model we have to call with data
     */
    public function fetchData(ModelInterface $model)
    {
        $this->result = $this->getResultInstance()->bind($this);

        foreach ($this->result as $item) {
            // First check if an item exists
            if ($model->itemExists($item['id'])) {
                $model->updateItem($item);

                // If the item is deleted, an update
                // is done before, because the update
                // might still be relevant
                if ($this->isDeleted($item)) {
                    $model->deleteItem($item['id']);
                }
            } elseif (!$this->isDeleted($item)) {
                $model->createItem($item);
            }
        }
    }

    /**
     * Do the request that is loaded
     * @return array Array from the json response;
     */
    public function getData()
    {
        $json = $this->asJson();
        $client = $this->getGuzzle();
        $this->log('info', 'send request:' . $json);
        $res = $client->post($this->url, ['body' => $json]);
        $data =$res->json();
        $this->log('debug', 'answer:' . $res);
        $this->settings = $data['settings'];
        return $data;
    }

    /**
     * Sets the data from when we are fetching data
     * @param $time string Timestamp from the last id
     * @param $itemId int Last id plus one
     */
    public function setFrom($time, $itemId)
    {
        if (!is_int($time)) {
            if (($time = strtotime($time)) === false) {
                throw new \InvalidArgumentException('Invalid time format');
            }
        }
        $this->since = $time;
        $this->startId = $itemId;
    }

    /**
     * Returns the type of this request
     * @return int Type
     */
    public function getType()
    {
        return $this->type;
    }
}
