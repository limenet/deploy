<?php

namespace limenet\Deploy\Strategies;

class AlwaysGoodStrategy implements StrategyInterface
{
    public function checkValidRequest() : bool
    {
        return true;
    }

    public function isBranch(string $branch) : bool
    {
        return true;
    }

    public function isTag() : bool
    {
        return true;
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
