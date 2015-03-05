<?php
namespace CodeYellow\Sync\Server\Model;

/**
 * Interface for transforming data that is fetched from the database
 */
interface TransformInterface
{
    /**
     * Transforms data fetched from the query
     * before it is send to the client
     *
     * @param array $dataSet the complete dataset
     */
    public function transform(array &$dataSet);
}
