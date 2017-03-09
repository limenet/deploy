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
}
