<?php
namespace CodeYellow\Sync\Proxy\Controller;
trait Proxy
{
    /**
     * Routes a request to the correct url
     * @param string What to sync
     */
    public function sync($what)
    {
        $config = \Config::get('packages/codeyellow/sync/config');

        if (!isset($config['servers'][$what])) {
            \App::abort(404);
        }
        $server = $config['servers'][$what];
        $input = file_get_contents('php://input');
        $this->doRequest($server, $input);

    }

    private function doRequest($server, $input)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->post($server['url'], ['body' => $input]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            \App::abort($e->getResponse()->getStatusCode(), 'An error has occured. URL:' . $server['url']. ' input:' . $input);
            die;
        }
        echo $res->getBody();
    }
}