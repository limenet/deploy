<?php

namespace limenet\Deploy\Strategies;

class ValidRequestInvalidBranchTagStrategy extends BaseStrategy
{
    public function checkValidRequest(): bool
    {
        return true;
    }

    public function isBranch(string $branch): bool
    {
        return false;
    }

    public function isTag(): bool
    {
        return false;
    }
}
