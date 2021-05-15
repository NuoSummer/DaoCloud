<?php
declare (strict_types=1);
/**
 * @copyright xingyong Liu
 * @version   1.0.0
 * @link
 */

namespace App\Service\Tron\Contract;

use App\Service\Tron\Address;
use App\Service\Tron\Transaction;

/**
 * WalletInterface
 *
 * @author  刘兴永(aile8880@qq.com)
 * @package App\Service\Tron\Contract
 */
interface WalletInterface
{
    public function generateAddress(): Address;

    public function createAccount(Address $ownerAddress, Address $accountAddress): Transaction;

    public function validateAddress(Address $address): bool;

    public function privateKeyToAddress(string $privateKeyHex): Address;

    public function balance(Address $address, array $contract = []): float;

    public function transfer(Address $from, Address $to, float $amount, array $contract = []): Transaction;
}