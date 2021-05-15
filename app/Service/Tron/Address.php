<?php
declare (strict_types=1);
/**
 * @copyright 深圳市易果网络科技有限公司
 * @version   1.0.0
 * @link      https://dayiguo.com
 */

namespace App\Service\Tron;

use App\Kernel\Utils\Hash;

/**
 * Address
 *
 * @author  刘兴永(aile8880@qq.com)
 * @package App\Service\Tron
 */
class Address
{
    /**
     * @var string
     */
    private string $privateKey, $address;

    const ADDRESS_SIZE        = 34;
    const ADDRESS_PREFIX      = '41';
    const ADDRESS_PREFIX_BYTE = '0x41';

    /**
     * Address constructor.
     *
     * @param string $address
     * @param string $privateKey
     */
    public function __construct(string $address, string $privateKey = '')
    {
        $this->address    = $address;
        $this->privateKey = $privateKey;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (strlen($this->address) !== Address::ADDRESS_SIZE) {
            return false;
        }
        $address = base58check2HexString($this->address);
        $bin     = hex2bin($address);
        if (strlen($bin) !== 25) {
            return false;
        }
        if (strpos($bin, (string)self::ADDRESS_PREFIX_BYTE) !== 0) {
            return false;
        }
        $checkSum  = substr($bin, 21);
        $address   = substr($bin, 0, 21);
        $hash0     = Hash::SHA256($address);
        $hash1     = Hash::SHA256($hash0);
        $checkSum1 = substr($hash1, 0, 4);
        if ($checkSum === $checkSum1) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getHexAddress(): string
    {
        return base58check2HexString($this->address);
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }
}