<?php
declare (strict_types=1);
/**
 * @copyright 深圳市易果网络科技有限公司
 * @version   1.0.0
 * @link      https://dayiguo.com
 */

namespace App\Kernel\Utils;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * Logger
 *
 * @author  刘兴永(aile8880@qq.com)
 * @package App\Kernel\Utils
 */
class Logger
{
    /**
     * @param string $name
     * @param string $group
     *
     * @return LoggerInterface|StdoutLoggerInterface
     */
    public static function get(string $name, string $group = 'default')
    {
        if (config('app_env') === 'dev') {
            return ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }
}