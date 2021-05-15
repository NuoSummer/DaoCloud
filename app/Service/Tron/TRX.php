<?php
declare (strict_types=1);
/**
 * @copyright 深圳市易果网络科技有限公司
 * @version   1.0.0
 * @link      https://dayiguo.com
 */

namespace App\Service\Tron;

use App\Service\Tron\Contract\WalletInterface;

/**
 * TRX
 *
 * @author  刘兴永(aile8880@qq.com)
 * @package App\Service\Tron
 */
class TRX extends Tron implements WalletInterface
{
    const CONTRACT_ADDRESS_MAP = [];

    /**
     * 获取账户余额
     *
     * @param Address $address
     * @param array   $contract
     *
     * @return float
     */
    public function balance(Address $address, array $contract = []): float
    {
        $result = $this->getAccount($address);
        return $this->formatAmount($result['balance']);
    }

    /**
     * 格式化金额
     *
     * @param int $balance
     *
     * @return float
     */
    public function formatAmount(int $balance): float
    {
        return $balance / pow(10, 6);
    }

    /**
     * 转账
     *
     * @param Address $from
     * @param Address $to
     * @param float   $amount
     * @param array   $contract
     *
     * @return Transaction
     */
    public function transfer(Address $from, Address $to, float $amount, array $contract = []): Transaction
    {
        $tron = new \IEXBase\TronAPI\Tron();
        $tron->setAddress($from->getAddress());
        $tron->setPrivateKey($from->getPrivateKey());
        $result = $this->httpPost('wallet/createtransaction', [
            'to_address'    => $to->getHexAddress(),
            'owner_address' => $from->getHexAddress(),
            'amount'        => $tron->toTron($amount)
        ]);
        // 广播交易
        $this->broadcastTransaction($from, $result);
        return new Transaction($result['txID'], $result['raw_data']);
    }
}