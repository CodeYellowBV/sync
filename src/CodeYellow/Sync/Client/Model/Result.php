<?php
namespace CodeYellow\Sync\Client\Model;

use CodeYellow\Sync\Type;

class Result implements ResultInterface, Type
{

    private $request;
    private $count = 0;
    private $remaining;
    private $data = array();

    /**
     * Bind the result with a new request
     *
     * @param Request $request The request that is done
     * @return Result Self
     */
    public function bind(Request $request)
    {
        $this->request = $request;

        $this->lastId = $this->request->getOption('startId', 1) - 1;
        $this->lastTime = $this->request->getOption('since', 0);
        $this->remaining = 1;

        return $this;
    }

    /**
     * Adds another request to the result
     * @param string $json The request to be added
     */
    private function addData($result)
    {
        $this->count += $result['count'];
        $this->remaining = $result['remaining'];
        $this->data = array_merge($this->data, $result['data']);
    }

    /********* ITERATOR****************/
    private $currentId = 0;
    private $lastId;
    private $lastTime = 0;
    public function current()
    {
        if ($this->valid()) {
            $data = $this->data[$this->currentId];
            $this->lastId = $data['id'];
            $this->lastTime = $data[$this->request->getType() == static::TYPE_NEW ? 'created_at' : 'updated_at'];
            return $data;
        }
        // Return null if the element does not exists. This is stupid, an exception should be thrown, but
        // this is what the interface tells us to do
        return null;
    }

    public function key()
    {
        return $this->currentId;
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
        if (isset($this->lastId) && $this->remaining == 0) {
            return false;
        }
        $this->request->setFrom((int) $this->lastTime, $this->lastId + 1);
        $this->addData($this->request->getData());

        // Check if this item is set now
        return isset($this->data[$this->currentId]);
    }
}
