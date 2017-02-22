<?php

use PHPUnit\Framework\TestCase;
use limenet\Deploy\Deploy;

class DeployTest extends TestCase {

    function testBranch() : void
    {
        $deploy = new Deploy();
        $deploy->setBranch('master');

        $this->assertSame('master', $deploy->getBranch());
    }
}