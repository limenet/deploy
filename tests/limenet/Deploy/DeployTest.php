<?php

use limenet\Deploy\Deploy;
use limenet\Deploy\PostDeployAdapterTempFile;
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

    public function testBasepathNotSet() : void
    {
        $this->expectException(Exception::class);
        $deploy = new Deploy();
        $deploy->getBasepath();
    }

    public function testBasepathNonGit() : void
    {
        $this->expectException(Exception::class);
        $deploy = new Deploy();
        $deploy->setBasepath(sys_get_temp_dir());
    }

    public function testBasepathIsGit() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setBasepath(BASEPATH));

        $this->assertSame(BASEPATH, $deploy->getBasepath());
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

    public function testIncompleteDeploy() : void
    {
        $this->expectException(Exception::class);
        (new Deploy())->run();
    }

    public function testCompleteDeploy() : void
    {
        $deploy = $this->getMockBuilder(Deploy::class)
            ->setMethods(['updateCode'])
            ->getMock();
        $deploy->expects($this->any())
            ->method('updateCode')
            ->will($this->returnValue(['output' => 'mocked', 'returnValue' => '0']));

        $deploy->setBasepath(BASEPATH);
        $deploy->setStrategy(new AlwaysGoodStrategy());
        $deploy->addAdapter(new PostDeployAdapterTempFile());
        $this->assertTrue($deploy->run());
        $this->assertFileExists(sys_get_temp_dir().'/limenet-deploy');
        unlink(sys_get_temp_dir().'/limenet-deploy');
    }
}
