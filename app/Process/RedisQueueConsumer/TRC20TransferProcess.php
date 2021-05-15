<?php

declare(strict_types=1);

namespace App\Process\RedisQueueConsumer;

use Hyperf\AsyncQueue\Process\ConsumerProcess;

/**
 * @package App\Process\RedisQueueConsumer
 */
class TRC20TransferProcess extends ConsumerProcess
{
    /**
     * @var string
     */
    protected $queue = 'TRC20Transfer';

    /**
     * @param \Swoole\Coroutine\Server|\Swoole\Server $server
     * @return bool
     */
    public function isEnable($server): bool
    {
        return true;
    }
}