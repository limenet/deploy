<?php

namespace limenet\Deploy;

use Curl\Curl;
use ReflectionClass;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class Deploy
{
    private $basepath;

    private $branch = 'master';

    private $env;

    private $payload;

    private $telegram;

    private $rollbar;

    private $version;

    private $cleanCache;

    private $postDeployAdapters;

    public function addAdapter(AdapterInterface $adapter)
    {
        $reflect = new ReflectionClass($adapter);
        if ($reflect->implementsInterface(PostDeployAdapterInterface::class)) {
            $this->postDeployAdapters[] = $adapter;
        }
    }

    public function basepath(string $basepath)
    {
        $this->basepath = $basepath;
    }

    public function branch(string $branch)
    {
        $this->branch = $branch;
    }

    public function env(string $env)
    {
        $this->env = $env;
    }

    public function rollbar(array $rollbar)
    {
        $this->rollbar = $rollbar;
    }

    public function version(callable $version)
    {
        $this->version = $version;
    }

    public function cleanCache(callable $cleanCache)
    {
        $this->cleanCache = $cleanCache;
    }

    private function getVersion()
    {
        if (is_callable($this->version)) {
            return call_user_func($this->version);
        }

        return false;
    }

    private function runCleanCache()
    {
        if (is_callable($this->cleanCache)) {
            return call_user_func($this->cleanCache);
        }

        return false;
    }

    public function run()
    {
        if (!$this->checkValidRequest()) {
            header('HTTP/1.1 403 Unauthorized', true, 403);
            die();
        }

        header('Content-Type: text/json');

        $this->payload = json_decode(Request::createFromGlobals()->request->get('payload') ?? $json, true);

        if (!$this->checkBranch()) {
            echo json_encode(['status' => 'notmybranch--notatag-aintnobodygottimefordat']);

            return;
        }

        $this->updateCode();
        $this->runCleanCache();

        foreach ($this->postDeployAdapters as $adapter) {
            $adapter->run($this, $this->payload);
        }

        if (!empty($this->rollbar)) {
            $this->rollbarDeploy();
        }

        echo json_encode(['status' => 'gitpull-composerup-happylife']);
    }

    /**
     * Checks whether  an incoming request is authorized i.e. whether it's coming from GitHub.
     *
     * @see https://help.github.com/articles/what-ip-addresses-does-github-use-that-i-should-whitelist/
     *
     * @return bool
     */
    protected function checkValidRequest() : bool
    {
        $request = Request::createFromGlobals();

        $originatingIp = $request->server->has('HTTP_CF_CONNECTING_IP') ? $request->server->get('HTTP_CF_CONNECTING_IP') : $request->server->get('REMOTE_ADDR');

        return $isIpAllowed = IpUtils::checkIp($originatingIp, ['192.30.252.0/22', '2620:112:3000::/44']);
    }

    /**
     * Checks whether the branch in the payload matches this pikapp installation.
     *
     * @return bool
     */
    protected function checkBranch() : bool
    {
        if ($this->branch === 'master') {
            return strpos($this->payload['ref'], 'refs/tags/') !== false;
        } elseif ($this->branch === 'dev-master') {
            return $this->payload['ref'] === 'refs/heads/master';
        } else {
            return $this->payload['ref'] === 'refs/heads/'.$this->branch;
        }
    }

    /**
     * Executes the actual deployment commands.
     *
     * @return array
     */
    protected function updateCode()
    {
        $output = [];
        $returnValue = 0;

        $updateMaster = 'ls'; // dummy

        if ($this->branch !== 'master') {
            $updateMaster = '&& git checkout master && git pull && git checkout '.$this->branch;
        }

        $commands = [
            'git reset --hard HEAD',
            'git pull',
            $updateMaster,
            'composer install --no-dev',
            'yarn install --production',
        ];

        foreach ($commands as $command) {
            exec('cd '.$this->basepath.' && '.$command, $output, $returnValue);
        }

        return [
            'output'      => $output,
            'returnValue' => $returnValue,
        ];
    }

    /**
     * Notifies Rollbar about the new version.
     *
     * @return void
     */
    protected function rollbarDeploy()
    {
        $curl = new Curl();
        $curl->post('https://api.rollbar.com/api/1/deploy/', [
            'access_token'   => $this->rollbar['token'],
            'environment'    => $this->env,
            'revision'       => $this->getVersion(),
            'local_username' => 'limenet/deploy',
        ]);
    }
}
