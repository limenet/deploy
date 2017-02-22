<?php

namespace limenet\Deploy;

use ReflectionClass;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class Deploy
{
    private $basepath;

    private $branch = 'master';

    private $env;

    private $payload;

    private $version;

    private $cleanCache;

    private $postDeployAdapters = [];

    public function addAdapter(AdapterInterface $adapter) : bool
    {
        $reflect = new ReflectionClass($adapter);
        if ($reflect->implementsInterface(PostDeployAdapterInterface::class)) {
            $this->postDeployAdapters[] = $adapter;

            return true;
        }

        return false;
    }

    public function checkAdapterAdded(AdapterInterface $adapter) : bool
    {
        foreach ($this->postDeployAdapters as $loadedAdapter) {
            if ($adapter instanceof $loadedAdapter) {
                return true;
            }
        }

        return false;
    }

    public function getBasepath() : string
    {
        return $this->basepath;
    }

    public function setBasepath(string $basepath) : bool
    {
        $this->basepath = $basepath;

        return true;
    }

    public function getBranch() : string
    {
        return $this->branch;
    }

    public function setBranch(string $branch) : bool
    {
        $this->branch = $branch;

        return true;
    }

    public function getEnv() : string
    {
        return $this->env;
    }

    public function setEnv(string $env) : bool
    {
        $this->env = $env;

        return true;
    }

    public function setVersion(callable $version) : bool
    {
        $this->version = $version;

        return true;
    }

    public function setCleanCache(callable $cleanCache) : bool
    {
        $this->cleanCache = $cleanCache;

        return true;
    }

    public function getVersion()
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

    public function run() : void
    {
        if (!$this->checkValidRequest()) {
            header('HTTP/1.1 403 Unauthorized', true, 403);
            die();
        }

        header('Content-Type: text/json');

        $this->payload = json_decode(Request::createFromGlobals()->request->get('payload'), true);

        if (!$this->checkBranch()) {
            echo json_encode(['status' => 'notmybranch--notatag-aintnobodygottimefordat']);

            return;
        }

        $this->updateCode();
        $this->runCleanCache();

        foreach ($this->postDeployAdapters as $adapter) {
            $adapter->run($this, $this->payload);
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

        return IpUtils::checkIp($originatingIp, ['192.30.252.0/22', '2620:112:3000::/44']);
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
    protected function updateCode() : array
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
}
