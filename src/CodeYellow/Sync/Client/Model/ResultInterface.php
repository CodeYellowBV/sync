<?php
namespace CodeYellow\Sync\Client\Model;
interface ResultInterface extends \Iterator
{
    /**
     * Create a new result object
     *
     * @param Request $request The request that is done
     */
    public function bind(Request $request);
}