<?php
/**
 * Created by PhpStorm.
 * User: zogxray
 * Date: 13.09.18
 * Time: 18:28
 */

namespace Micseres\MicroServiceReactor\EchoLogger;

/**
 * Class Logger
 * @package Micseres\MicroServiceReactor\EchoLogger
 */
class Logger
{
    /**
     * @param string $message
     * @param array $params
     * @param int $level
     */
    public function log(string $message, array $params, $level = 0): void
    {
        echo "{$level} ".$message." ".json_encode($params)."\r\n";
    }
}