<?php
namespace CodeYellow\Sync\Proxy\Controller;
trait Proxy
{

    public function getConfig($item)
    {
        return $this->app['config']->get($item);
    }

    public function getApp()
    {
        return $this->app['app'];
    }

    public function getGuzzle()
    {
        return new \GuzzleHttp\Client();
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