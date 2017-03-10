<?php

namespace limenet\Deploy\Strategies;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class TravisStrategy extends AbstractWebhookPayloadStrategy
{
    public function checkValidRequest() : bool
    {
        return (int) $this->payload['status'] === 0;
    }

    public function isBranch(string $branch) : bool
    {
        if (in_array($branch, ['tag', 'master'], true)) {
            return $this->isTag();
        } elseif ($branch === 'dev-master') {
            return $this->payload['branch'] === 'master';
        } else {
            return $this->payload['branch'] === $branch;
        }
    }

    public function isTag() : bool
    {
        // currently no support
        return false;
    }

    public function getCommitHash() : string
    {
        return $this->payload['commit'] ?? '';
    }

    public function getCommitUrl() : string
    {
        return $this->payload['compare_url'] ?? '';
    }

    public function getCommitMessage() : string
    {
        return $this->payload['message'] ?? '';
    }

    public function getCommitUsername() : string
    {
        return $this->payload['author_name'] ?? '';
    }
}
