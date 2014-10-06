<?php
namespace CodeYellow\Sync\Proxy\Controller;
trait Proxy
{

    private $guzzle;
    private $app;
    private $config;

    /**
     * Get an item from the configuration
     * @param string $Item to get from config
     * @return val Value of $item in config
     */
    public function getConfig($item)
    {
        return isset($this->config) ? $this->config : $this->app['config']->get($item);
    }

    /**
     * Set the config for the proxy
     * @param Illuminate\Config\Repository $config Config file 
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the app
     * @return Illuminate\Foundation\Application Application
     */
    public function getApp()
    {
        return isset($this->app) ? $this->app : $this->app['app'];
    }

    /**
     * Set the app
     * @param Illuminate\Foundation\Application $app
     */
    public function setApp(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Return the guzzle client
     * @param \GuzzleHttp\Client The client
     */
    public function getGuzzle()
    {
        return isset($this->guzzle) ? $this->guzzle : new \GuzzleHttp\Client();
    }

    /**
     * Set the guzzle client
     * @param \GuzzleHttp\Client The client
     */
    public function setGuzzle(\GuzzleHttp\Client $client)
    {
        $this->guzzle = $client;
    }


    /**
     * Routes a request to the correct url
     * @param string What to sync
     * @return string response
     */
    public function sync($what)
    {
        $config = $this->getConfig('packages/codeyellow/sync/config');

        if (!isset($config['servers'][$what])) {
            $this->getApp()->abort(404);
            return;
        }
        $server = $config['servers'][$what];
        $input = file_get_contents('php://input');
        return $this->doRequest($server, $input);

    }

    /**
     * Do a request to the server
     * @param string $server Server to query
     * @param string $input Input query given
     * @return string Body of the answer for the request
     */
    private function doRequest($server, $input)
    {
        try {
            $client = $this->getGuzzle();
            $res = $client->post($server['url'], ['body' => $input]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $this->getApp()->abort($e->getResponse()->getStatusCode(), 'An error has occured. URL:' . $server['url']. ' input:' . $input);
            return;
        }
        return $res->getBody();
    }
}