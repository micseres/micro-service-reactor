<?php
/**
 * @author: Andrii yakovlev <yawa20@gmail.com>
 * @since : 28.09.18
 */

namespace Micseres\MicroServiceReactor;

interface MicroServiceReactorInterface
{
    /**
     *
     */
    public function process(): void;

    /**
     * @param array $controllerClosure
     */
    public function setControllerClosure(array $controllerClosure): void;

    /**
     * @param array $loggerClosure
     */
    public function setLoggerClosure(array $loggerClosure): void;
}
