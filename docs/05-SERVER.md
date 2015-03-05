# Server
The server is the most important entity in the sync module. The server is the owner of the data, and makes sure that the request for the data it owns is satisfied. The server only works with Eloquent. 

## Accepting the request
The first step is to accept the request that the client has given, and to translate it in a model. This is fairly easy. A Request model needs to be created with the raw request data as parameter. 

### Example code
Example code that can be placed in a controller to accept a request:
```
$rawRequest = file_get_contents('php://input');
$request = new \CodeYellow\Sync\Server\Model\Request($rawRequest);
```

## Fetch the result
If the request is accepted, and a Request model is created, the next step is to fullfill the request. This is done by the doSync method. This method needs a \Illuminate\Database\Query\Builder object. If you use an Eloquent, calling getQuery() will give you this object. Before you pass the builder you are allowed to filter the set of data that is synchronised. For example, if you have a database with users, and an endpoint that synchronises all users above 18, you can filter this beforehand. Every query that is given as an argument needs three columns: id, created_at, and updated_at. When the request doSync is called additional constraints will be added to the request to ensure that the correct records are synchronised. After this is done, a Result object is generated and returned. 

In a second parameter the settings object needs to be set. The settings object is needed to set the specific database parameters correctly, furthermore it allows to easily extend the funcionality of Sync, by creating a custom Settings class. 

As an additional third parameter the maximum batch size can be given. The actual batch size will be determined by the minimum of the server provided maximum batch size, the client provided maximum batch size and the actual amount of records that need to be synced. When the maximum batch size is not given by the server, then the given maximum batch size from the client is used. This may cause the server to use a lot of resources! Therefore it is wise to set a batch size. Furthermore, setting the batch size too low will cause the client to have to make a lot of request.

As an additional fourth parameter a transformer can be given. A transformer should inherit the transformerInterface. This transformer gets the complete dataset, and the server is able to transform this data before it is send to the user. 

### Example Code
```
$rawRequest = file_get_contents('php://input');
$request = new \CodeYellow\Sync\Server\Model\Request($rawRequest);
$settings = new \CodeYellow\Sync\Server\Model\Settings();
$model = new Model(); // Where Model is an eloquent model
$model->where('age' > 18);
$result = $request->doSync($model->getQuery(),$settings, 10);
```

## The Result model
After a result is fetched, a Result model is returned. This model keeps track of all the results of the request. The only thing left to do is to return this result model to the user. To do this the result needs to create a json string. This json string can be obtained by calling the asJson() method of the Result model. The model then assures that the generated json is in the correct format for the client to be interpreted. 