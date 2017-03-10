<?php

namespace limenet\Deploy\Strategies;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractWebhookPayloadStrategy implements StrategyInterface
{
    protected $payload;
    protected $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? Request::createFromGlobals();
        $this->payload = json_decode($this->request->request->get('payload'), true);
    }
}
