<?php
declare (strict_types=1);
/**
 * @copyright 深圳市易果网络科技有限公司
 * @version   1.0.0
 * @link      https://dayiguo.com
 */

namespace App\Service\Tron;

use App\Service\Tron\Contract\WalletInterface;
use App\Service\Tron\Support\Formatter;
use App\Service\Tron\Support\Utils;

use Hyperf\Utils\Codec\Json;
use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\{Address as TypeAddress, Boolean, Bytes, DynamicBytes, Integer, Str, Uinteger};

/**
 * TRC20
 *
 * @author  刘兴永(aile8880@qq.com)
 * @package App\Service\Tron
 */
class TRC20 extends Tron implements WalletInterface
{
    /**
     * USDT代币参数配置
     *
     * @var array
     */
    const CONTRACT_USDT = [
        'symbol'   => 'USDT',
        'address'  => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
        'decimals' => 6,
        'abi'      => [
            'balanceOf' => '{"constant":true,"inputs":[{"name":"account","type":"address"}],"name":"balanceOf","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"}'
        ]
    ];

    /**
     * 代币参数映射
     *
     * @var array
     */
    const CONTRACT_ADDRESS_MAP = [
        'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t' => self::CONTRACT_USDT
    ];

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
        $ethAbi         = new Ethabi([
            'address'      => new TypeAddress,
            'bool'         => new Boolean,
            'bytes'        => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int'          => new Integer,
            'string'       => new Str,
            'uint'         => new Uinteger,
        ]);
        $contractParams = [$address->getHexAddress()];
        $parameters     = substr($ethAbi->encodeParameters(Json::decode($contract['abi']['balanceOf'], true), $contractParams), 2);
        $result         = $this->httpPost('/wallet/triggersmartcontract', [
            'contract_address'  => base58check2HexString($contract['address']),
            'function_selector' => 'balanceOf(address)',
            'parameter'         => $parameters,
            'owner_address'     => $address->getHexAddress()
        ]);
        return $this->formatAmount($contract['address'], $result['constant_result'][0]);
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
        $toFormat     = Formatter::toAddressFormat($to->getHexAddress());
        $amount       = Utils::toMinUnitByDecimals($amount, $contract['decimals']);
        $numberFormat = Formatter::toIntegerFormat($amount);

        $result = $this->httpPost('/wallet/triggersmartcontract', [
            'contract_address'  => base58check2HexString($contract['address']),
            'function_selector' => 'transfer(address,uint256)',
            'parameter'         => "{$toFormat}{$numberFormat}",
            'fee_limit'         => 100000000,
            'call_value'        => 0,
            'owner_address'     => $from->getHexAddress(),
        ]);
        // 广播交易
        $this->broadcastTransaction($from, $result['transaction']);
        return new Transaction($result['transaction']['txID'], $result['transaction']['raw_data']);
    }

    /**
     * 获取转账记录
     *
     * @param int $id
     *
     * @return array
     */
    public function getTransfers(int $id = 0): array
    {
        [$blockId, $result] = $id > 0 ? $this->getBlockById($id) : $this->getNowBlock();
        $transfers = [];
        foreach ($result['TRC20'] as $item) {
            if (!isset($item['type']) || $item['type'] !== 'transfer') {
                continue;
            }
            if (($amount = $this->formatAmount(hexString2Base58check($item['contract_address']), $item['amount'])) === false) {
                continue;
            }
            $item['amount'] = $amount;
            $transfers[]    = $item;
        }
        return $transfers;
    }

    /**
     * 格式化金额
     *
     * @param string $contract_address
     * @param string $hex
     *
     * @return float|int|false
     */
    public function formatAmount(string $contract_address, string $hex)
    {
        if (!isset(self::CONTRACT_ADDRESS_MAP[$contract_address])) {
            return false;
        }
        $hex_number = preg_replace('/^0+/', '', $hex);
        if (empty($hex_number)) {
            return 0;
        }
        $num = gmp_init($hex_number, 16);
        return gmp_strval($num) / pow(10, self::CONTRACT_ADDRESS_MAP[$contract_address]['decimals']);
    }
}