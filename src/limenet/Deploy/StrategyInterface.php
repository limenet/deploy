<?php

namespace limenet\Deploy;

interface StrategyInterface
{
    public function checkValidRequest() : bool;

    public function getPayloadBranch() : string;
}
