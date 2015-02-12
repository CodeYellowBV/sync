<?php
namespace CodeYellow\Sync\Client\Model;
interface ModelInterface
{
    /**
     * Returns a boolean indicating if an id exists already
     * @param $itemId Integer the id of the item
     * @return boolean indicating if an item with id $id exists
     */
    public function itemExists($itemId);

    /**
     * Create a new item.
     *
     * @param array $data
     * @return boolean Do we have to continue
     */
    public function createItem(array $data);

    /**
     * Updates an existing item
     * @param array $data The new data for this object
     * @return boolean Do we have to continue
     */
    public function updateItem(array $data);

    /**
     * Delete an existing item
     * @param int $itemId The id of the item to delete
     * @return boolean Do we have to continue
     */
    public function deleteItem($itemId);
}