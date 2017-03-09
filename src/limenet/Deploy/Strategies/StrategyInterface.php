<?php

namespace limenet\Deploy;

interface StrategyInterface
{
    public function checkValidRequest() : bool;

    public function isBranch(string $branch) : string;

    public function isTag() : bool;
}
