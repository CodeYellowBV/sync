# Client
The client is the most used layer in the Sync chain. The client initiates a request with the parent to synchronise data. The server will than hopefully respond to this request.

## Creating a request objects
Creating an object to do a request is easy. You only have to provide the url of the server or proxy you want to query, and an array of options. Options that are not set will be set to their default value.

### Sample code
The next code will start a request, requesting all the new entries, in a batch of 10, that were created before 1412607797 and were created since 1412600000, starting with id 1.

```
$options = array('type' => Request::TYPE_NEW, 'limit' => 10, 'before' => 1412607797, 'since' => 1412600000, 'startId' => 1);
$request = new \CodeYellow\Sync\Request('Example.org/api/test', $options);
```
### Different options
**Type**: Request type. Either 'Request::TYPE_NEW' for requesting new objects, or 'Request::TYPE_MODIFIED' for new and updated objects. This option has no standard value
**Limit**: Maximum batch size of request. If limit is set to 10, and 23 records are available, minimal 3 requests will be done. If this option is not set, the limit will be set to infinity. Note that this value can be overridden by the responding server. 
**Before**: Provide an upper bound for the unix timestamp the requested resources are last created/updated. If this is not set, or it is a date in the future, than it will be set to the current time. 
**Since**: Provide a lower bound for the unix timestamp the requested resources are last created/updated.. If not set it will be set to 0, i.e. all resources are returned.

## Doing a request
Requests are done in a very abstract manner. If you have set the request parameters, you just tell the Request class that you want to receive data. The Request class than is responsible for initiating the correct request, and handling the data that is returned. For this the fetchData method is introduced. This method needs an interface with several methods, the exact interface will be explained further on in the document. Note that the limit that was set while creating a request is only a limit on the batch size. I.e. if there are 87 new records, and you are calling the fetchData, all 87 will be returned. However 9 requests are done for this.

### Sample code
```
$options = array('type' => Request::TYPE_NEW, 'limit' => 10, 'before' => 1412607797, 'since' => 1412600000, 'startId' => 1);
$request = new \CodeYellow\Sync\Request('Example.org/api/test', $options);

$model = new Model(); // Make sure that Model implements the ModelInterface
$request->fetchData($model);
```

## ModelInterface
When the fetchData method is called a model witch implements the ModelInterface needs to be provided as argument. The ModelInterface consists of 4 methods to do CRUD operations. A complete explanation of those methods is provided below:

### itemExists($id)
Returns if an item exists in the database with id $id. 

### createItem(array $data)
Tells the model that a new item needs to be created. Paramater $data gives all the data that is needed for this new model. In the implementation an subset of the data can be stored in one or more models. Data does not have to be stored one on one.

### updateItem(array $data)
Same as createItem, however the dataobject is already present. $data['id'] will be set to indicate which data object needs to be updated.

### deleteItem($id) 
Delete an item from the database with id $id.

### What is done when
There are a couple of different scenarios for the modelinterface. This section describes what happens in which scenario for a better understanding of what the modelinterface does

**Server gives an updated item, and _itemExists_ returns false**

In this case the _createItem_ method is called, since the item did not exists yet at the client

**Server gives an updated item, and _itemExists_ returns true**

In this case the _updateItem_ method is called, since the item does already exists at the client, and some update is done to the item

**Server gives a deleted item, and _itemExists_ returns false**

The Entity does not exist and both the server and the client. For the client it is just the same as if the entity never existed. Therefore, no method is called, and the deleted entity is ignored. 

**Server gives a deleted item, and _itemExists_ returns true**

In this case first the _updateItem_ method is called, and then the _deleteItem_ method is called. The deleteItem method is called because the item exists at the client, and does not exist anymore on the server.

The reason that the _updateItem_ method is called beforehand is because an item might need to be updated before being deleted, otherwise database constraints may be violated. To demonstrate this, take this scenario:

A user class exists with a unique constraint on the username, and the id field is used to identify users. Both the server and client use only softdeletes for the user. User $a is present at both the server and the client. Now the server edits the username of $a, and then deletes $a. After this the server creates a User $b, with the same username as $a had originally. Now the clients syncs. If the updateItem method is not called, User $a will be softdeleted, but still has it's original username. Now a User $b is created with the same username as $a has, which violated the unique constraint on the username. Therefore the models are first updated and then saved.


### Example code
Example code for a Eloquent ORM implementation of the Model:

```
class Model extends Eloquent implements \CodeYellow\Sync\Client\Model\ModelInterface 
{
	protected $table = 'example';
	public function itemExists($id) {
		return !is_null($this->find($id));
	}

	public function createItem(array $data) {
		$model = new Model();
		$model->set($data);
		$model->save();
	}

	public function udpateItem(array $data) {
		$model = $this->find($data['id']);
		$model->set($data);
		$model->save();
	}
}
```