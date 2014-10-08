# Proxy
The proxy class is a middle layer that may provide an abstract way of your components to communicate without knowing where the other component resides. The advantage for this is that different clients and servers only need to be aware of the proxy, rather than of all devices. This means that if you move a server, you only need to modify the url in the proxy, rather than modifying the url for all clients. The proxy is made specifically for Laravel only. 

## Configuration
The proxy needs to be configured for all the possible synchronisations. A sample config file is included in the source. This sample configuration can be eddited after doing the ```php artisan config:publish vendor/package``` command. Laravel will than place the config file in the config directory. It is than possible to edti this configuration. 

### Configuration sample
If you have entities /users/ and /example/ that can be synchronised, this may be a configuration:
```
return array(
    'servers' => array(
        'users' => array(
            'url' => 'example.com/sync/users',
        ),
        'example' => array(
            'url' => 'other-domain.org/sync/example',
        )
    )
);
```

## Proxying a sync
The Proxy class is a trait that needs to be used in a controller. The sync method than will proxy your sync request and return the answer gotten from the server. Sync only needs a parameter indicating what will be synced. Note that the whole request content is redirected to the server.

### Sample code
A sample code for a simple proxy:
Controller:
```
class ExampleController extends Controller {
	use \CodeYellow\Sync\Proxy\Controller\Proxy;
}
```

Routes.php:
```
Route::post('sync/{what}', ['as' => 'sync', 'uses' => 'ExampleController@sync']);
```
