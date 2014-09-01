<?php
namespace CodeYellow\Sync\Server\Model;
class Result
{
    private $count;
    private $remaining;
    private $data;

    /**
     * Create a new sync result
     * @param array $data The data to sync
     * @param int $totalRecords How many records are there in total (remaining + data)
     */
    public function __construct(array $data, $totalRecords)
    {
        if (!is_int($totalRecords)) {
            throw new InvalidArgumentException('syncResult: totalRecords must be an integer');
        }

        $this->count = count($data);
        $this->remaining = max($totalRecords - $this->count, 0);

        foreach ($data as $key => $result) {
            $this->data[$key] = (array) $result;
        }
    }

    /**
     * Return an array representation of this object
     * @return array The result
     */
    public function asArray()
    {
        return [
            'count' => $this->count,
            'remaining' => $this->remaining,
            'data' => $this->data
        ];
    }

    /**
     * Return a json representation of this object
     * @return string Json representation of the result
     */
    public function asJson()
    {
        return json_encode($this->asArray());
    }
}