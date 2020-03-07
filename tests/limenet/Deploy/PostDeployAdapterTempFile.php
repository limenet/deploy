<?php

namespace limenet\Deploy;

class PostDeployAdapterTempFile implements PostDeployAdapterInterface
{
    public function config(array $config): void
    {
    }

    public function run(Deploy $deploy): bool
    {
        touch(sys_get_temp_dir().'/limenet-deploy');

        return true;
    }
}
