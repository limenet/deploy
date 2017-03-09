<?php

use limenet\Deploy\Deploy;
use limenet\Deploy\Exceptions\UnauthorizedException;
use limenet\Deploy\Strategies\AlwaysBadStrategy;
use limenet\Deploy\Strategies\AlwaysGoodStrategy;
use limenet\Deploy\Strategies\GithubStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AdapterTest extends TestCase
{
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
