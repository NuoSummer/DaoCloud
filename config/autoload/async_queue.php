<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Hyperf\AsyncQueue\Driver\RedisDriver;

return [
    'default' => [
        'driver' => Hyperf\AsyncQueue\Driver\RedisDriver::class,
        'channel' => 'queue',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => 1,
    ],
    'TRC20Transfer' => [
        'driver'         => RedisDriver::class,
        'channel'        => 'Queue:TRC20Transfer',
        'timeout'        => 3,
        'retry_seconds'  => 10,
        'handle_timeout' => 10,
        'processes'      => 1,
        'concurrent'     => [
            'limit' => 1,
        ],
    ],
];
