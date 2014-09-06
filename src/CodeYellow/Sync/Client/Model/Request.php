<?php
namespace CodeYellow\Sync\Client\Model;
use CodeYellow\Sync\Type;
class Request implements Type
{
    protected $options = ['url','type', 'limit', 'before', 'since','startId'];
    protected $url;
    
    protected $type;
    protected $limit;
    protected $before;
    protected $since;
    protected $startId;

    protected $result;

    /**
     * Construct a new request
     * @param array $options Array of the options to be set.
     */
    public function __construct(array $options)
    {
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
        if (!in_array($name, $this->options)) {
            throw new Exception('Option with name ' . $name . ' does not exist');
        }
        $this->$name = $val;

        switch ($name) {
            case 'type':
                if (!in_array($val, [static::TYPE_NEW, static::TYPE_MODIFIED])) {
                    throw new \Exception(
                        'Unexpected type ' . $val .
                        ' Chose between ' . static::TYPE_MODIFIED . ' or ' . static::TYPE_NEW
                    );
                }
            break;
        }
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
     * Fetch more data
     * @param ModelInterface $model The model we have to call with data
     */
    public function fetchData(ModelInterface $model)
    {
        $this->result = new Result($this);
        foreach ($this->result as $item) {
            // First check if an item exists
            if ($model->itemExists($item['id'])) {
                if (isset($item['deleted']) && $item['deleted']) {
                    $model->deleteItem($item['id']);
                } else {
                    $model->updateItem($item);
                }
            } else if (!isset($item['deleted']) || !$item['deleted']) {
                $model->createItem($item);
            }

            // If the model is not exists, and the model needs to be deleted, do nothing
        }
    }

    public function getData()
    {
        $json = $this->asJson();
        $client = new \GuzzleHttp\Client();
        $res = $client->post($this->url, ['body' => $json]);

        return $res->json();
    }

    /**
     * Sets the data from when we are fetching data
     * @param $time int Timestamp from the last id
     * @param $id int Last id plus one
     */
    public function setFrom($time, $id)
    {
        $this->time = $time;
        $this->startId = $id;
    }

    public function getType()
    {
        return $this->type;
    }

}
