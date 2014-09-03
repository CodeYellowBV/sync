<?php
namespace CodeYellow\Sync\Client\Model;
use CodeYellow\Sync\Type;

class Result implements \Iterator, Type{

    private $request;
    private $count = 0;
    private $remaining;
    private $data = array();

    /**
     * Create a new result object
     *
     * @param Request The request that is done
     * @param string $json Json encoded answer
     */
    public function __construct(Request $request, $json)
    {
        $this->request = $request;
    }

    /**
     * Adds another request to the result
     * @param string $json The request to be added
     */
    public function addData($json) {
        $result = json_decode($json);
        $this->count += $result->count;
        $this->remaining = $result->remaining;
        $this->data = array_merge($this->data, $result->data);
    }

    /********* ITERATOR****************/
    private $currentId = 0;
    private $lastId;
    private $lastTime;
    public function current()
    {
        if ($this->valid()) {
            $data = $this->data[$this->currentId];
            $this->currentId = $this->data->id;
            $this->currentTime = $this->request->type == static::TYPE_NEW ? 'created_at' : 'updated_at';
            return $data;
        } else {
            // Return null if the element does not exists. This is stupid, an exception should be thrown, but
            // this is what the interface tells us to do
            return null;
        }

    }

    public function key()
    {
        return $this->currrentId;
    }

    public function next()
    {
        $this->currentId++;
    }

    public function rewind()
    {
        $this->currentId = 0;
    }

    public function valid()
    {
        // Check if this item exists. If so, this is ok
        if (isset($this->data[$this->currentId])) {
            return true;
        }

        // If we have done a request, and no more items are remaining, it is not valid
        if (isset($this->lastId) && $this->request->remaining == 0) {
            return false;
        }

        $this->request->setFrom($this->currentTime, $this->currentId + 1);
        $this->addData($this->request->getData());

        // Check if this item is set now
        return isset($this->data[$this->currentId]);
    }

} 