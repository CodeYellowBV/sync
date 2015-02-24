<?php
namespace CodeYellow\Sync\Client\Model;

use CodeYellow\Sync\Type;

class Request implements Type
{
    protected $options = ['type', 'limit', 'before', 'since','startId'];
    protected $url;
    
    protected $type;
    protected $limit;
    protected $before;
    protected $since;
    protected $startId;

    protected $result;

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

    public function getOption($name)
    {
        return $this->$name;
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
        return (isset($item['deleted']) && $item['deleted']) ||
            (isset($item['deleted_at']) && !is_null($item['deleted_at']));
                
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
            if ($model->itemExists($item['id']) && $this->isDeleted($item)) {
                $model->deleteItem($item['id']);
            } elseif ($model->itemExists($item['id'])) {
                $model->updateItem($item);
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
        $res = $client->post($this->url, ['body' => $json]);
        return $res->json();
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
