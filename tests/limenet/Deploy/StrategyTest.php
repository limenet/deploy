<?php

use limenet\Deploy\Deploy;
use limenet\Deploy\Exceptions\UnauthorizedException;
use limenet\Deploy\Strategies\AlwaysBadStrategy;
use limenet\Deploy\Strategies\GithubStrategy;
use limenet\Deploy\Strategies\TravisStrategy;
use limenet\Deploy\Strategies\ValidRequestInvalidBranchTagStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StrategyTest extends TestCase
{
    public function testDoubleSetStrategy(): void
    {
        $deploy = new Deploy();
        $this->assertTrue($deploy->setStrategy(new limenet\Deploy\Strategies\AlwaysGoodStrategy()));
        $this->assertFalse($deploy->setStrategy(new limenet\Deploy\Strategies\AlwaysGoodStrategy()));
    }

    public function testDeployAlwaysBadStrategy(): void
    {
        $this->expectException(UnauthorizedException::class);
        $deploy = new Deploy();
        $deploy->setStrategy(new AlwaysBadStrategy());
        $deploy->run();
    }

    public function testDeployValidRequestInvalidBranchTag(): void
    {
        $deploy = new Deploy();
        $deploy->setStrategy(new ValidRequestInvalidBranchTagStrategy());
        $this->assertFalse($deploy->run());
    }

    public function testGithubStrategyValidRequest(): void
    {
        $emptyRequest = new Request([], [], [], [], [], []);
        $validIpRequest = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.30.252.0']);
        $validIpRequestCf = new Request([], [], [], [], [], ['HTTP_CF_CONNECTING_IP' => '192.30.252.0']);

        $this->assertFalse((new GithubStrategy($emptyRequest))->checkValidRequest());
        $this->assertTrue((new GithubStrategy($validIpRequest))->checkValidRequest());
        $this->assertTrue((new GithubStrategy($validIpRequestCf))->checkValidRequest());
    }

    public function testGithubStrategyTag(): void
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

    public function testGithubStrategyBranch(): void
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

    public function testGithubStrategyCommitFields(): void
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

        $this->assertIsString((new GithubStrategy($emptyRequest))->getCommitHash());
        $this->assertIsString((new GithubStrategy($emptyRequest))->getCommitUrl());
        $this->assertIsString((new GithubStrategy($emptyRequest))->getCommitMessage());
        $this->assertIsString((new GithubStrategy($emptyRequest))->getCommitUsername());

        $this->assertSame('deadbeef', (new GithubStrategy($commitRequest))->getCommitHash());
        $this->assertSame('http://example.com', (new GithubStrategy($commitRequest))->getCommitUrl());
        $this->assertSame('hello world', (new GithubStrategy($commitRequest))->getCommitMessage());
        $this->assertSame('John Doe', (new GithubStrategy($commitRequest))->getCommitUsername());
    }

    public function testGithubStrategyFullDelivery(): void
    {
        $this->assertFileExists(DATA_WEBHOOK_GITHUB);
        $payload_json = file_get_contents(DATA_WEBHOOK_GITHUB);
        $payload = json_decode($payload_json);
        $commitRequest = new Request([], ['payload' => $payload_json], [], [], [], []);

        $this->assertSame($payload->head_commit->id, (new GithubStrategy($commitRequest))->getCommitHash());
        $this->assertSame($payload->head_commit->url, (new GithubStrategy($commitRequest))->getCommitUrl());
        $this->assertSame($payload->head_commit->message, (new GithubStrategy($commitRequest))->getCommitMessage());
        $this->assertSame($payload->head_commit->author->username, (new GithubStrategy($commitRequest))->getCommitUsername());
        $this->assertFalse((new GithubStrategy($commitRequest))->isTag());
        $this->assertFalse((new GithubStrategy($commitRequest))->isBranch('master'));
        $this->assertFalse((new GithubStrategy($commitRequest))->isBranch('dev-master'));
        $this->assertTrue((new GithubStrategy($commitRequest))->isBranch('changes'));
    }

    public function testTravisStrategyFullDeliveryBranch(): void
    {
        $this->assertFileExists(DATA_WEBHOOK_TRAVIS_BRANCH);
        $payload_json = file_get_contents(DATA_WEBHOOK_TRAVIS_BRANCH);
        $payload = json_decode($payload_json);
        $branchRequest = new Request([], ['payload' => $payload_json], [], [], [], []);

        $this->assertSame($payload->commit, (new TravisStrategy($branchRequest))->getCommitHash());
        $this->assertSame($payload->compare_url, (new TravisStrategy($branchRequest))->getCommitUrl());
        $this->assertSame($payload->message, (new TravisStrategy($branchRequest))->getCommitMessage());
        $this->assertSame($payload->author_name, (new TravisStrategy($branchRequest))->getCommitUsername());
        $this->assertFalse((new TravisStrategy($branchRequest))->isTag());
        $this->assertFalse((new TravisStrategy($branchRequest))->isBranch('master'));
        $this->assertTrue((new TravisStrategy($branchRequest))->isBranch('dev-master'));
        $this->assertFalse((new TravisStrategy($branchRequest))->isBranch('changes'));
    }

    public function testTravisStrategyFullDeliveryTag(): void
    {
        $this->assertFileExists(DATA_WEBHOOK_TRAVIS_TAG);
        $payload_json = file_get_contents(DATA_WEBHOOK_TRAVIS_TAG);
        $payload = json_decode($payload_json);
        $tagRequest = new Request([], ['payload' => $payload_json], [], [], [], []);

        $this->assertSame($payload->commit, (new TravisStrategy($tagRequest))->getCommitHash());
        $this->assertSame($payload->compare_url, (new TravisStrategy($tagRequest))->getCommitUrl());
        $this->assertSame($payload->message, (new TravisStrategy($tagRequest))->getCommitMessage());
        $this->assertSame($payload->author_name, (new TravisStrategy($tagRequest))->getCommitUsername());
        $this->assertTrue((new TravisStrategy($tagRequest))->isTag());
        $this->assertTrue((new TravisStrategy($tagRequest))->isBranch('master'));
        $this->assertFalse((new TravisStrategy($tagRequest))->isBranch('dev-master'));
        $this->assertFalse((new TravisStrategy($tagRequest))->isBranch('changes'));
    }
}
