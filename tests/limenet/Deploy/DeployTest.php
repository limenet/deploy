<?php

use PHPUnit\Framework\TestCase;
use limenet\Deploy\Deploy;

class DeployTest extends TestCase {

    function testBranch() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setBranch('master'));

        $this->assertSame('master', $deploy->getBranch());
    }

    function testBasepath() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setBasepath(__DIR__));

        $this->assertSame(__DIR__, $deploy->getBasepath());
    }

    function testEnv() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setEnv('testing'));

        $this->assertSame('testing', $deploy->getEnv());
    }

    function testVersion() : void
    {
        $deploy = new Deploy();

        $this->assertFalse($deploy->getVersion());

        $this->assertTrue($deploy->setVersion(function() {
            return '1.0.2-beta+deadbeef';
        }));

        $this->assertSame('1.0.2-beta+deadbeef', $deploy->getVersion());
    }

    function testAddPostDeployAdapter() : void
    {
        $adapter = $this->getMockBuilder('limenet\Deploy\AdapterInterface')->getMock();
        $adapterPostDeploy = $this->getMockBuilder('limenet\Deploy\PostDeployAdapterInterface')->getMock();

        $deploy = new Deploy();

        $this->assertFalse($deploy->checkAdapterAdded($adapter));
        $this->assertFalse($deploy->checkAdapterAdded($adapterPostDeploy));

        $this->assertFalse($deploy->addAdapter($adapter));
        $this->assertTrue($deploy->addAdapter($adapterPostDeploy));

        $this->assertFalse($deploy->checkAdapterAdded($adapter));
        $this->assertTrue($deploy->checkAdapterAdded($adapterPostDeploy));
    }
}
