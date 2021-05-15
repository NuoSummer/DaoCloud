<?php

namespace App\Kernel\Utils;

/**
 * Class Hash
 *
 * @package App\Kernnel\Utils
 */
class Hash
{
    /**
     * Hashing SHA-256
     *
     * @param      $data
     * @param bool $raw
     *
     * @return string
     */
    public static function SHA256($data, $raw = true): string
    {
        return hash('sha256', $data, $raw);
    }

    /**
     * Double hashing SHA-256
     *
     * @param $data
     *
     * @return string
     */
    public static function sha256d($data): string
    {
        return hash('sha256', hash('sha256', $data, true), true);
    }

    /**
     * Hashing RIPEMD160
     *
     * @param      $data
     * @param bool $raw
     *
     * @return string
     */
    public static function RIPEMD160($data, $raw = true): string
    {
        return hash('ripemd160', $data, $raw);
    }
}
