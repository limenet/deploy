<?php

use limenet\Deploy\Deploy;
use PHPUnit\Framework\TestCase;

class DeployTest extends TestCase
{
    public function testBranch() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setBranch('master'));

        $this->assertSame('master', $deploy->getBranch());
    }

    public function testBasepath() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setBasepath(__DIR__));

        $this->assertSame(__DIR__, $deploy->getBasepath());
    }

    public function testEnv() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setEnv('testing'));

        $this->assertSame('testing', $deploy->getEnv());
    }

    public function testVersion() : void
    {
        $deploy = new Deploy();

        $this->assertFalse($deploy->getVersion());

        $this->assertTrue($deploy->setVersion(function () {
            return '1.0.2-beta+deadbeef';
        }));

        $this->assertSame('1.0.2-beta+deadbeef', $deploy->getVersion());
    }

    public function testCleanCache() : void
    {
        $deploy = new Deploy();

        $this->assertTrue($deploy->setCleanCache(function () {
            // clear cache...
            return 'cache cleaned';
        }));
    }

    public function testAddAdapter() : void
    {
        $adapter = $this->getMockBuilder('limenet\Deploy\AdapterInterface')->getMock();

        $deploy = new Deploy();

        $this->assertFalse($deploy->isAdapterAdded($adapter));

        $this->assertFalse($deploy->addAdapter($adapter));

        $this->assertFalse($deploy->isAdapterAdded($adapter));
    }

    public function testAddPostDeployAdapter() : void
    {
        $adapter = $this->getMockBuilder('limenet\Deploy\PostDeployAdapterInterface')->getMock();

        $deploy = new Deploy();

        $this->assertFalse($deploy->isAdapterAdded($adapter));

        $this->assertTrue($deploy->addAdapter($adapter));

        $this->assertTrue($deploy->isAdapterAdded($adapter));
    }
}
