<?php

use limenet\Deploy\Deploy;
use limenet\Deploy\Strategies\AlwaysBadStrategy;
use limenet\Deploy\Strategies\AlwaysGoodStrategy;
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

    public function testStrategyNotSet() : void
    {
        $this->expectException(Exception::class);
        (new Deploy())->run();
    }

    public function testDoubleSetStrategy() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setStrategy(new limenet\Deploy\Strategies\AlwaysGoodStrategy()));
        $this->assertFalse($deploy->setStrategy(new limenet\Deploy\Strategies\AlwaysGoodStrategy()));
    }

    public function testAlwaysGoodStrategy() : void
    {
        $this->assertTrue((new Deploy())->setStrategy(new AlwaysGoodStrategy()));
        $this->assertTrue((new AlwaysGoodStrategy())->checkValidRequest());
        $this->assertTrue((new AlwaysGoodStrategy())->isTag());
        $this->assertTrue((new AlwaysGoodStrategy())->isBranch('some-branch'));
    }

    public function testAlwaysBadStrategy() : void
    {
        $this->assertTrue((new Deploy())->setStrategy(new AlwaysBadStrategy()));
        $this->assertFalse((new AlwaysBadStrategy())->checkValidRequest());
        $this->assertFalse((new AlwaysBadStrategy())->isTag());
        $this->assertFalse((new AlwaysBadStrategy())->isBranch('some-branch'));
    }
}
