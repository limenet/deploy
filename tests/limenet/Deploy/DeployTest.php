<?php

use limenet\Deploy\Deploy;
use limenet\Deploy\Exceptions\UnauthorizedException;
use limenet\Deploy\Strategies\AlwaysBadStrategy;
use Symfony\Component\HttpFoundation\Request;
use limenet\Deploy\Strategies\AlwaysGoodStrategy;
use limenet\Deploy\Strategies\GithubStrategy;
use PHPUnit\Framework\TestCase;

class DeployTest extends TestCase
{
    public function testBranch() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setBranch('master'));

        $this->assertSame('master', $deploy->getBranch());
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

    public function testIncompleteDeploy() : void
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

    public function testDeployAlwaysBadStrategy() : void
    {
        $this->expectException(UnauthorizedException::class);
        $deploy = new Deploy();
        $deploy->setStrategy(new AlwaysBadStrategy());
        $deploy->run();
    }

    public function testGithubStrategyValidRequest() : void
    {
        $emptyRequest = new Request([], [], [], [], [], []);
        $validIpRequest = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.30.252.0']);
        $validIpRequestCf = new Request([], [], [], [], [], ['HTTP_CF_CONNECTING_IP' => '192.30.252.0']);

        $this->assertFalse((new GithubStrategy($emptyRequest))->checkValidRequest());
        $this->assertTrue((new GithubStrategy($validIpRequest))->checkValidRequest());
        $this->assertTrue((new GithubStrategy($validIpRequestCf))->checkValidRequest());
    }

    public function testGithubStrategyTag() : void
    {
        $emptyRequest = new Request([], [], [], [], [], []);
        $branchRequest = new Request([], ['payload' => json_encode(["ref" => "refs/heads/develop"])], [], [], [], []);
        $tagRequest = new Request([], ['payload' => json_encode(["ref" => "refs/tags/v4.2.0"])], [], [], [], []);

        $this->assertFalse((new GithubStrategy($emptyRequest))->isTag());
        $this->assertFalse((new GithubStrategy($branchRequest))->isTag());
        $this->assertTrue((new GithubStrategy($tagRequest))->isTag());
    }

    public function testGithubStrategyBranch() : void
    {
        $emptyRequest = new Request([], [], [], [], [], []);
        $developBranchRequest = new Request([], ['payload' => json_encode(["ref" => "refs/heads/develop"])], [], [], [], []);
        $masterBranchRequest = new Request([], ['payload' => json_encode(["ref" => "refs/heads/master"])], [], [], [], []);
        $tagRequest = new Request([], ['payload' => json_encode(["ref" => "refs/tags/v4.2.0"])], [], [], [], []);

        $this->assertFalse((new GithubStrategy($emptyRequest))->isBranch('some-branch'));
        $this->assertTrue((new GithubStrategy($developBranchRequest))->isBranch('develop'));
        $this->assertTrue((new GithubStrategy($masterBranchRequest))->isBranch('dev-master'));
        $this->assertFalse((new GithubStrategy($masterBranchRequest))->isBranch('master'));
        $this->assertFalse((new GithubStrategy($tagRequest))->isBranch('some-branch'));
        $this->assertTrue((new GithubStrategy($tagRequest))->isBranch('tag'));
    }
}
