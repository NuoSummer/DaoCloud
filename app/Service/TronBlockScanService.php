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
namespace App\Service;

use App\Job\TRC20TransferJob;
use App\Service\Tron\Tron;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

/**
 * 波场区块扫描逻辑.
 */
class TronBlockScanService
{
    /**
     * @Inject
     */
    protected Redis $redis;

    public function getNowTransfer()
    {
        try {
            [$blockId, $transactions] = di(Tron::class)->getNowBlock();
            $key = 'BlockScannedHeight:Tron';
            // 判断是否存在历史扫描区块，如果存在则对比当前与之前的高度差，并投递异步任务以补充区块高度同步交易
            if (($lastBlockId = $this->redis->get($key)) !== false) {
                for ($i = $lastBlockId; $i < $blockId; ++$i) {
                    if ($i !== $blockId && $i !== $lastBlockId) {
                        var_dump('补充区块: ' . $i);
                    }
                }
            }
            var_dump('最新区块: ' . $blockId);
            // 设置最新区块高度
            $this->redis->set($key, $blockId);
            $queue = di(DriverFactory::class)->get('TRC20Transfer');
            foreach ($transactions['TRC20'] as $trc20) {
                if (! isset($trc20['type']) || $trc20['type'] !== 'transfer') {
                    continue;
                }
                $queue->push(new TRC20TransferJob(
                    $trc20['contract_address'],
                    $trc20['owner_address'],
                    $trc20['to_address'],
                    $trc20['amount'],
                    $trc20['transaction_id'],
                    $blockId,
                ));
            }
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
        }
    }
}
