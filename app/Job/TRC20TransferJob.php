<?php

declare(strict_types=1);

namespace App\Job;

use App\Service\Tron\TRC20;
use Hyperf\AsyncQueue\Job;

/**
 * @package App\Job
 */
class TRC20TransferJob extends Job
{
    /**
     * 合约地址
     *
     * @var string
     */
    public string $contract_address;
    /**
     * 转账方
     *
     * @var string
     */
    public string $owner_address;
    /**
     * 接收方
     *
     * @var string
     */
    public string $to_address;
    /**
     * 转账金额
     *
     * @var string
     */
    public string $amount;

    /**
     * 交易ID
     *
     * @var string
     */
    public string $transaction_id;

    /**
     * 区块高度
     *
     * @var int
     */
    public int $block_id;

    /**
     * TRC20TransferJob constructor.
     *
     * @param string $contract_address
     * @param string $owner_address
     * @param string $to_address
     * @param string $amount
     * @param string $transaction_id
     * @param int    $block_id
     */
    public function __construct(string $contract_address, string $owner_address, string $to_address, string $amount, string $transaction_id, int $block_id)
    {
        $this->contract_address = $contract_address;
        $this->owner_address    = $owner_address;
        $this->to_address       = $to_address;
        $this->amount           = $amount;
        $this->transaction_id   = $transaction_id;
        $this->block_id         = $block_id;
    }

    public function handle()
    {
        $amount = di(TRC20::class)->formatAmount(hexString2Base58check($this->contract_address), $this->amount);
        if ($amount !== false) {
            var_dump(sprintf('%s => %s: %s', $this->owner_address, $this->to_address, $amount));
        }
        sleep(1);
    }
}