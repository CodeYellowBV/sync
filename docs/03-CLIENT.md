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