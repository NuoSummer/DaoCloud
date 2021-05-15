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
namespace App\Service\Tron;

use App\Kernel\Utils\Logger;
use App\Service\Tron\Exception\RequestException;
use App\Service\Tron\Exception\TransactionException;
use App\Service\Tron\Exception\TronErrorException;
use App\Service\Tron\Support\Key as SupportKey;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\Codec\Json;
use IEXBase\TronAPI\Exception\TronException;
use Phactor\Key;
use Psr\Container\ContainerInterface;

/**
 * Tron.
 */
class Tron
{
    /**
     * 接入点.
     *
     * @var string
     */
    const ENDPOINT = 'https://api.trongrid.io/';

    // const ENDPOINT = 'https://api.shasta.trongrid.io/';

    protected Client $httpClient;

    protected ContainerInterface $container;

    /**
     * TRX constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->httpClient = $container->get(ClientFactory::class)->create([
            'base_uri' => self::ENDPOINT,
        ]);
    }

    /**
     * 生成钱包地址
     */
    // public function generateAddress(): Address
    // {
    //     $result = $this->httpGet('wallet/generateaddress');
    //     if (!isset($result['address'])) {
    //         throw new GenerateAddressException('生成地址失败');
    //     }
    //     return new Address($result['address'], $result['privateKey']);
    // }

    /**
     * 生成钱包地址
     */
    public function generateAddress(): Address
    {
        $key = new Key([
            'private_key_hex' => '',
            'private_key_dec' => '',
            'public_key' => '',
            'public_key_compressed' => '',
            'public_key_x' => '',
            'public_key_y' => '',
            'generation_time' => '',
        ]);
        $attempts = 0;
        $validAddress = false;

        do {
            if ($attempts++ === 5) {
                throw new TronErrorException('Could not generate valid key');
            }
            $keyPair = $key->GenerateKeypair();
            $privateKeyHex = $keyPair['private_key_hex'];
            $pubKeyHex = $keyPair['public_key'];
            if (strlen($pubKeyHex) % 2 !== 0) {
                continue;
            }
            $addressHex = Address::ADDRESS_PREFIX . SupportKey::publicKeyToAddress($pubKeyHex);
            $addressBase58 = SupportKey::getBase58CheckAddress($addressHex);
            $address = new Address($addressBase58, $privateKeyHex);
            $validAddress = true;
            // $validAddress  = $this->validateAddress($address);
        } while (! $validAddress);

        return $address;
    }

    /**
     * 创建链上账户.
     */
    public function createAccount(Address $ownerAddress, Address $accountAddress): Transaction
    {
        // 创建链上账号
        $result = $this->httpPost('wallet/createaccount', [
            'owner_address' => $ownerAddress->getHexAddress(),
            'account_address' => $accountAddress->getHexAddress(),
        ]);
        if (! isset($result['txID'])) {
            throw new TransactionException(hex2bin($result['message']));
        }
        // 广播交易
        $this->broadcastTransaction($ownerAddress, $result);
        return new Transaction($result['txID'], $result['raw_data']);
    }

    /**
     * 验证地址是否有效.
     */
    public function validateAddress(Address $address): bool
    {
        $result = $this->httpPost('/wallet/validateaddress', [
            'address' => $address->getAddress(),
        ]);
        return $result['result'];
    }

    /**
     * 私钥根据转地址
     */
    public function privateKeyToAddress(string $privateKeyHex): Address
    {
        $addressHex = Address::ADDRESS_PREFIX . SupportKey::privateKeyToAddress($privateKeyHex);
        $addressBase58 = SupportKey::getBase58CheckAddress($addressHex);
        $address = new Address($addressBase58, $privateKeyHex);
        if (! $this->validateAddress($address)) {
            throw new TronErrorException('Invalid private key');
        }
        return $address;
    }

    /**
     * 获取链上账户.
     */
    public function getAccount(Address $address): array
    {
        // 获取链上账号
        return $this->httpPost('wallet/getaccount', [
            'address' => $address->getHexAddress(),
        ]);
    }

    /**
     * 获取当前区块数据.
     */
    public function getNowBlock(): array
    {
        $result = $this->httpPost('walletsolidity/getnowblock');
        return [
            $result['block_header']['raw_data']['number'],
            $this->formatTransactions($result['transactions'] ?? []),
        ];
    }

    /**
     * 通过区块ID获取区块数据.
     */
    public function getBlockById(int $id): array
    {
        $result = $this->httpPost('walletsolidity/getblockbynum', [
            'num' => $id,
        ]);
        return [
            $result['block_header']['raw_data']['number'],
            $this->formatTransactions($result['transactions'] ?? []),
        ];
    }

    protected function httpPost(string $uri, array $data = []): array
    {
        try {
            $response = $this->httpClient->post($uri, [
                'json' => $data,
            ]);
            $content = $response->getBody()->getContents();
            $result = Json::decode($content, true);
        } catch (GuzzleException $e) {
            Logger::get('http')->error(sprintf('%s%s => %s', self::ENDPOINT, $uri, $e->getMessage()), $data);
            throw new RequestException($e->getMessage());
        }
        if (isset($result['result']['code'])) {
            throw new TransactionException(hex2bin($result['result']['message']));
        }
        return $result;
    }

    /**
     * @return null|mixed
     */
    protected function httpGet(string $uri, array $data = [])
    {
        try {
            $response = $this->httpClient->get($uri);
            $content = $response->getBody()->getContents();
            $result = Json::decode($content, true);
        } catch (GuzzleException $e) {
            Logger::get('http')->error(sprintf('%s%s => %s', self::ENDPOINT, $uri, $e->getMessage()), $data);
            throw new RequestException($e->getMessage());
        }
        if (isset($result['result']['code'])) {
            throw new TransactionException(hex2bin($result['result']['message']));
        }
        return $result;
    }

    /**
     * 广播交易.
     */
    protected function broadcastTransaction(Address $from, array $transaction)
    {
        $tron = new \IEXBase\TronAPI\Tron();
        $tron->setAddress($from->getAddress());
        $tron->setPrivateKey($from->getPrivateKey());
        // 广播交易
        try {
            $transactionResult = $this->httpPost('wallet/broadcasttransaction', $tron->signTransaction($transaction));
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }
        if (! isset($transactionResult['result']) || $transactionResult['result'] !== true) {
            throw new TransactionException('Transfer Fail');
        }
    }

    /**
     * 格式化区块交易数据.
     */
    private function formatTransactions(array $transactions): array
    {
        $result = [
            'TRX' => [],
            'TRC10' => [],
            'TRC20' => [],
        ];
        foreach ($transactions as $transaction) {
            if ($transaction['ret'][0]['contractRet'] !== 'SUCCESS') {
                continue;
            }
            foreach ($transaction['raw_data']['contract'] as $contract) {
                // TRX
                if ($contract['type'] === 'TransferContract') {
                    $result['TRX'][] = [
                        'type' => 'transfer',
                        'owner_address' => $contract['parameter']['value']['owner_address'],
                        'to_address' => $contract['parameter']['value']['to_address'],
                        'transaction_id' => $transaction['txID'],
                    ];
                    continue;
                }
                // TRC10（暂不解析）
                if ($contract['type'] === 'TransferAssetContract') {
                    continue;
                }
                // TRC20
                if ($contract['type'] === 'TriggerSmartContract') {
                    $contract_data = $contract['parameter']['value']['data'];
                    $data = [
                        'owner_address' => $contract['parameter']['value']['owner_address'],
                        'contract_address' => $contract['parameter']['value']['contract_address'],
                        'transaction_id' => $transaction['txID'],
                    ];
                    // 转账
                    if (strpos($contract_data, 'a9059cbb') === 0) {
                        $data['type'] = 'transfer';
                        $data['to_address'] = '41' . substr($contract_data, 32, 40);
                        $data['amount'] = preg_replace('/^0+/', '', substr($contract_data, 72));
                    }
                    $result['TRC20'][] = $data;
                }
            }
        }
        return $result;
    }
}
