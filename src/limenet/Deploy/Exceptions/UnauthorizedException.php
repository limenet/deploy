<?php

namespace limenet\Deploy\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct($message = 'HTTP 403: Unauthorized', $code = 0)
    {
        parent::__construct($message, $code);
    }
}
