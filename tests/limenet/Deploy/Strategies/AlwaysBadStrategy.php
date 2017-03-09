<?php

namespace limenet\Deploy\Strategies;

class AlwaysBadStrategy implements StrategyInterface
{
    public function checkValidRequest() : bool
    {
        return false;
    }

    public function isBranch(string $branch) : bool
    {
        return false;
    }

    public function isTag() : bool
    {
        return false;
    }

    public function getCommitHash() : string
    {
        return 'commit-hash';
    }

    public function getCommitUrl() : string
    {
        return 'commit-url';
    }

    public function getCommitMessage() : string
    {
        return 'commit-message';
    }

    public function getCommitUsername() : string
    {
        return 'commit-username';
    }
}
