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
namespace App\Kernel\Utils;

class RSA
{
    public static function encrypt(string $data, string $public_key): string
    {
        openssl_public_encrypt($data, $result, $public_key);

        return base64_encode($result);
    }

    public static function decrypt(string $data, string $private_key): string
    {
        openssl_private_decrypt(base64_decode($data), $result, $private_key);

        return $result;
    }
}
