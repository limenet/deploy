<?php

namespace limenet\Deploy;

use limenet\Deploy\Strategies\StrategyInterface;
use limenet\Deploy\Exceptions\UnauthorizedException;
use ReflectionClass;
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

    private $strategy;

    public function setStrategy(StrategyInterface $strategy) : bool
    {
        if ($this->strategy) {
            return false;
        }

        $this->strategy = $strategy;

        return true;
    }

    public function isStrategySet() : bool
    {
        return isset($this->strategy);
    }

    public function addAdapter(AdapterInterface $adapter) : bool
    {
        $reflect = new ReflectionClass($adapter);
        if ($reflect->implementsInterface(PostDeployAdapterInterface::class)) {
            $this->postDeployAdapters[] = $adapter;

            return true;
        }

        return false;
    }

    public function isAdapterAdded(AdapterInterface $adapter) : bool
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

    public function run() : bool
    {
        if (!$this->isStrategySet()) {
            throw new \Exception('No strategy set');
        }

        if (!$this->strategy->checkValidRequest()) {
            throw new UnauthorizedException;
        }

        header('Content-Type: text/json');

        $this->payload = json_decode(Request::createFromGlobals()->request->get('payload'), true);

        if (!$this->strategy->isBranch($this->getBranch())) {
            return false;
        }

        $this->updateCode();
        $this->runCleanCache();

        foreach ($this->postDeployAdapters as $adapter) {
            $adapter->run($this, $this->payload);
        }

        return true;
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

        if ($this->getBranch() !== 'master') {
            $updateMaster = '&& git checkout master && git pull && git checkout '.$this->getBranch();
        }

        $commands = [
            'git reset --hard HEAD',
            'git pull',
            $updateMaster,
            'composer install --no-dev',
            'yarn install --production',
        ];

        foreach ($commands as $command) {
            exec('cd '.$this->getBasepath().' && '.$command, $output, $returnValue);
        }

        return [
            'output'      => $output,
            'returnValue' => $returnValue,
        ];
    }
}
