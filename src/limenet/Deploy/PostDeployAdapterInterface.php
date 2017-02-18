<?php

namespace limenet\Deploy;

use Curl\Curl;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Telegram\Bot\Api as TelegramApi;

interface PostDeployAdapterInterface extends AdapterInterface
{
}