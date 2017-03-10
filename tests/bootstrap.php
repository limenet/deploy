<?php

require_once __DIR__.'/../vendor/autoload.php';

define('BASEPATH', realpath(__DIR__.'/../'));
define('DATA_WEBHOOK_GITHUB', realpath(__DIR__.'/data/webhook_github.json'));
define('DATA_WEBHOOK_TRAVIS_BRANCH', realpath(__DIR__.'/data/webhook_travis-branch.json'));
define('DATA_WEBHOOK_TRAVIS_TAG', realpath(__DIR__.'/data/webhook_travis-tag.json'));
