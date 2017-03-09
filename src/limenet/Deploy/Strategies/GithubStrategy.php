<?php

namespace limenet\Deploy\Strategies;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class GithubStrategy implements StrategyInterface
{
    private $payload;
    private $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? Request::createFromGlobals();
        $this->payload = json_decode($this->request->request->get('payload'), true);
    }

    public function checkValidRequest() : bool
    {
        $originatingIp = $this->request->server->has('HTTP_CF_CONNECTING_IP') ? $this->request->server->get('HTTP_CF_CONNECTING_IP') : $this->request->server->get('REMOTE_ADDR');

        // https://help.github.com/articles/what-ip-addresses-does-github-use-that-i-should-whitelist/
        return IpUtils::checkIp($originatingIp, ['192.30.252.0/22', '2620:112:3000::/44']);
    }

    public function isBranch(string $branch) : bool
    {
        if (in_array($branch, ['tag', 'master'], true)) {
            return $this->isTag();
        } elseif ($branch === 'dev-master') {
            return $this->payload['ref'] === 'refs/heads/master';
        } else {
            return $this->payload['ref'] === 'refs/heads/'.$branch;
        }
    }

    public function isTag() : bool
    {
        return strpos($this->payload['ref'], 'refs/tags/') !== false;
    }
}
