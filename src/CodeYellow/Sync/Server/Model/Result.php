<?php
namespace CodeYellow\Sync\Server\Model;

use \CodeYellow\Sync\Server\Model\SettingsInterface;

class Result
{
    private $count;
    private $remaining;
    private $data;
    private $settings;

    /**
     * Create a new sync result
     *
     * @param array $data The data to sync
     * @param int $totalRecords How many records are there in total (remaining + data)
     * @param TransformerInterface $transformer Transformer that transforms the dataset
     */
    public function __construct(
        array $data,
        $totalRecords,
        SettingsInterface $settings,
        $transformer
    ) {
        if ('' . (int)$totalRecords != $totalRecords) {
            throw new \InvalidArgumentException('syncResult: totalRecords must be an integer');
        }

        $this->count = count($data);
        $this->remaining = max($totalRecords - $this->count, 0);
        $this->settings = $settings;

        foreach ($data as &$result) {
            $result = (array) $result;
            foreach ($settings->getTimeFields() as $time) {
                if (isset($result[$time])) {
                    $result[$time] = $settings->toUnixTime($result[$time]);
                }
            }
        }


        if (!is_null($transformer)) {
            $transformer->transform($data);
        }

        $this->data = $data;
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
            'settings' => $this->settings->asArray(),
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
