<?php
return [
    'enable' => true,
    'crontab' => [
        (new \Hyperf\Crontab\Crontab())->setName('TronBlockScan')
        ->setRule('*/5 * * * * *')->setCallback([
            \App\Service\TronBlockScanService::class,
            'getNowTransfer'
        ])->setMemo('波场区块扫描任务')
    ]
];
