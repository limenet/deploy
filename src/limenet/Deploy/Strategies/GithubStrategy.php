<?php

namespace limenet\Deploy\Strategies;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class GithubStrategy extends AbstractWebhookPayloadStrategy
{
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
        }

        if ($branch === 'dev-master') {
            return $this->payload['ref'] === 'refs/heads/master';
        }

        return $this->payload['ref'] === 'refs/heads/'.$branch;
    }

    public function isTag() : bool
    {
        return strpos($this->payload['ref'], 'refs/tags/') !== false;
    }

    public function getCommitHash() : string
    {
        return $this->payload['head_commit']['id'] ?? '';
    }

    public function getCommitUrl() : string
    {
        return $this->payload['head_commit']['url'] ?? '';
    }

    public function getCommitMessage() : string
    {
        return $this->payload['head_commit']['message'] ?? '';
    }

    public function getCommitUsername() : string
    {
        return $this->payload['head_commit']['author']['username'] ?? '';
    }
}
