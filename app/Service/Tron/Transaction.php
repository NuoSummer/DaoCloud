<?php
declare (strict_types=1);
/**
 * @copyright 深圳市易果网络科技有限公司
 * @version   1.0.0
 * @link      https://dayiguo.com
 */

namespace App\Service\Tron;

/**
 * Transaction
 *
 * @author  刘兴永(aile8880@qq.com)
 * @package App\Service\Tron
 */
class Transaction
{
    /**
     * @var string
     */
    public string $txID;

    /**
     * @var array
     */
    public array $rawData;

    /**
     * Transaction constructor.
     *
     * @param string $txID
     * @param array  $rawData
     */
    public function __construct(string $txID, array $rawData)
    {
        $this->txID    = $txID;
        $this->rawData = $rawData;
    }

    /**
     * @return string
     */
    public function getTxID(): string
    {
        return $this->txID;
    }

    /**
     * @return array
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }
}