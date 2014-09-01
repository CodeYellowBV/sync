<?php
namespace CodeYellow\Sync\Proxy\Controller;
trait Proxy
{
    /**
     * Routes a request to the correct url
     */
    public function sync($what)
    {
        $config = \Config::get('packages/codeyellow/sync/config');

        if (!isset($config['servers'][$what])) {
            \App::abort(404);
        }
        $server = $config['servers'][$what];

    }
}