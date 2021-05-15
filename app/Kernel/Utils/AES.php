<?php

declare(strict_types=1);
/**
 * @copyright 深圳市易果网络科技有限公司
 * @version   1.0.0
 * @link      https://dayiguo.com
 */

namespace App\Kernel\Utils;

/**
 * AES加密
 *
 * @author  李想(928674263@qq.com)
 * @package App\Kernel\Util
 */
class AES
{
    /**
     * AES加密
     *
     * @param string $data
     * @param string $key
     * @param string $iv
     *
     * @return string
     */
    public static function encrypt(string $data, string $key, string $iv): string
    {
        return openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * AES解密
     *
     * @param string $data
     * @param string $key
     * @param string $iv
     *
     * @return false|string
     */
    public static function decrypt(string $data, string $key, string $iv)
    {
        return openssl_decrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}