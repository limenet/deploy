<?php

namespace limenet\Deploy;

interface StrategyInterface
{
    /**
     * Check whether the request is valid per-se.
     **.
     *
     * @return bool
     */
    public function checkValidRequest() : bool;

    public function getPayloadBranch() : string;
}
