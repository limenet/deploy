<?php

namespace limenet\Deploy\Strategies;

abstract class BaseStrategy implements StrategyInterface
{
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
