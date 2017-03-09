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
}
