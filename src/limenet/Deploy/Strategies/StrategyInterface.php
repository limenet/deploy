<?php

namespace limenet\Deploy\Strategies;

interface StrategyInterface
{
    public function checkValidRequest() : bool;

    public function isBranch(string $branch) : bool;

    public function isTag() : bool;

    public function getCommitHash() : string;

    public function getCommitUrl() : string;

    public function getCommitMessage() : string;

    public function getCommitUsername() : string;
}
