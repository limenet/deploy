<?php

namespace limenet\Deploy;

use Curl\Curl;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Telegram\Bot\Api as TelegramApi;

interface AdapterInterface
{
    /**
     * Configure the adapter (API keys etc.).
     * @param  array  $config
     * @return void
     */
    public function configure(array $config) : void;

    /**
     * Run the adapter.
     * @param  array  $payload
     * @return bool
     */
    public function run(array $payload) : bool;
}