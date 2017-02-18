<?php

namespace limenet\Deploy;

interface AdapterInterface
{
    /**
     * Configure the adapter (API keys etc.).
     *
     * @param array $config
     *
     * @return void
     */
    public function config(array $config) : void;

    /**
     * Run the adapter.
     *
     * @param array $payload
     *
     * @return bool
     */
    public function run(Deploy $deploy, array $payload) : bool;
}
