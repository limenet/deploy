<?php

use limenet\Deploy\Deploy;
use limenet\Deploy\Exceptions\UnauthorizedException;
use limenet\Deploy\Strategies\AlwaysBadStrategy;
use limenet\Deploy\Strategies\GithubStrategy;
use limenet\Deploy\Strategies\ValidRequestInvalidBranchTagStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StrategyTest extends TestCase
{
    public function testDoubleSetStrategy() : void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setStrategy(new limenet\Deploy\Strategies\AlwaysGoodStrategy()));
        $this->assertFalse($deploy->setStrategy(new limenet\Deploy\Strategies\AlwaysGoodStrategy()));
    }

    public function testDeployAlwaysBadStrategy() : void
    {
        $this->expectException(UnauthorizedException::class);
        $deploy = new Deploy();
        $deploy->setStrategy(new AlwaysBadStrategy());
        $deploy->run();
    }

    public function testDeployValidRequestInvalidBranchTag() : void
    {
        $deploy = new Deploy();
        $deploy->setStrategy(new ValidRequestInvalidBranchTagStrategy());
        $this->assertFalse($deploy->run());
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
        $branchRequest = new Request([], ['payload' => json_encode([
            'ref' => 'refs/heads/develop',
        ])], [], [], [], []);
        $tagRequest = new Request([], ['payload' => json_encode([
            'ref' => 'refs/tags/v4.2.0',
        ])], [], [], [], []);

        $this->assertFalse((new GithubStrategy($emptyRequest))->isTag());
        $this->assertFalse((new GithubStrategy($branchRequest))->isTag());
        $this->assertTrue((new GithubStrategy($tagRequest))->isTag());
    }

    public function testGithubStrategyBranch() : void
    {
        $emptyRequest = new Request([], [], [], [], [], []);
        $developBranchRequest = new Request([], ['payload' => json_encode([
            'ref' => 'refs/heads/develop',
        ])], [], [], [], []);
        $masterBranchRequest = new Request([], ['payload' => json_encode([
            'ref' => 'refs/heads/master',
        ])], [], [], [], []);
        $tagRequest = new Request([], ['payload' => json_encode([
            'ref' => 'refs/tags/v4.2.0',
        ])], [], [], [], []);

        $this->assertFalse((new GithubStrategy($emptyRequest))->isBranch('some-branch'));
        $this->assertTrue((new GithubStrategy($developBranchRequest))->isBranch('develop'));
        $this->assertTrue((new GithubStrategy($masterBranchRequest))->isBranch('dev-master'));
        $this->assertFalse((new GithubStrategy($masterBranchRequest))->isBranch('master'));
        $this->assertFalse((new GithubStrategy($tagRequest))->isBranch('some-branch'));
        $this->assertTrue((new GithubStrategy($tagRequest))->isBranch('tag'));
    }

    public function testGithubStrategyCommitFields() : void
    {
        $emptyRequest = new Request([], [], [], [], [], []);
        $commitRequest = new Request([], ['payload' => json_encode([
            'head_commit' => [
                'id'      => 'deadbeef',
                'url'     => 'http://example.com',
                'message' => 'hello world',
                'author'  => [
                    'username' => 'John Doe',
                ],
            ],
        ])], [], [], [], []);

        $this->assertInternalType('string', (new GithubStrategy($emptyRequest))->getCommitHash());
        $this->assertInternalType('string', (new GithubStrategy($emptyRequest))->getCommitUrl());
        $this->assertInternalType('string', (new GithubStrategy($emptyRequest))->getCommitMessage());
        $this->assertInternalType('string', (new GithubStrategy($emptyRequest))->getCommitUsername());

        $this->assertSame('deadbeef', (new GithubStrategy($commitRequest))->getCommitHash());
        $this->assertSame('http://example.com', (new GithubStrategy($commitRequest))->getCommitUrl());
        $this->assertSame('hello world', (new GithubStrategy($commitRequest))->getCommitMessage());
        $this->assertSame('John Doe', (new GithubStrategy($commitRequest))->getCommitUsername());
    }

    public function testGithubStrategyFullDelivery() : void
    {
        $this->assertFileExists(DATA_WEBHOOK_GITHUB);
        $commitRequest = new Request([], ['payload' => file_get_contents(DATA_WEBHOOK_GITHUB)], [], [], [], []);

        $this->assertSame('0d1a26e67d8f5eaf1f6ba5c57fc3c7d91ac0fd1c', (new GithubStrategy($commitRequest))->getCommitHash());
        $this->assertSame('https://github.com/baxterthehacker/public-repo/commit/0d1a26e67d8f5eaf1f6ba5c57fc3c7d91ac0fd1c', (new GithubStrategy($commitRequest))->getCommitUrl());
        $this->assertSame('Update README.md', (new GithubStrategy($commitRequest))->getCommitMessage());
        $this->assertSame('baxterthehacker', (new GithubStrategy($commitRequest))->getCommitUsername());
        $this->assertFalse((new GithubStrategy($commitRequest))->isTag());
        $this->assertFalse((new GithubStrategy($commitRequest))->isBranch('master'));
        $this->assertFalse((new GithubStrategy($commitRequest))->isBranch('dev-master'));
        $this->assertTrue((new GithubStrategy($commitRequest))->isBranch('changes'));
    }
}
