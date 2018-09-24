<?php
/**
 * Created by PhpStorm.
 * User: zogxray
 * Date: 12.09.18
 * Time: 15:31
 */

namespace Micseres\MicroServiceReactor;

use Micseres\MicroServiceDH\DiffieHellman;
use Micseres\MicroServiceDH\Exception\DiffieHellmanException;
use Micseres\MicroServiceEncrypt\DummyEncrypt;
use Micseres\MicroServiceEncrypt\EncryptInterface;
use Micseres\MicroServiceEncrypt\Exception\EncryptException;
use Micseres\MicroServiceEncrypt\OpenSSLEncrypt;
use Micseres\MicroServiceReactor\EchoLogger\Logger;
use Micseres\MicroServiceReactor\Exception\CallbackException;
use Micseres\MicroServiceReactor\Exception\ServiceException;
use Micseres\MicroServiceReactor\Exception\SocketException;
use Micseres\MicroServiceReactor\SystemRequest\Request;

/**
 * Class MicroServiceReactor
 * @package Micseres\MicroServiceReactor
 */
final class MicroServiceReactor
{
    const SLEEP_TIME = 1000;
    /**
     * @var string
     */
    private $ip;
    /**
     * @var string
     */
    private $port;

    /** @var resource */
    private $socket;

    /**
     * @var \Closure|null
     */
    private $controllerClosure = null;

    /**
     * @var \Closure|null
     */
    private $loggerClosure = null;

    /**
     * @var string
     */
    private $route;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var null|string
     */
    private $algorithm;

    /**
     * @var EncryptInterface
     */
    private $encrypt;

    /**
     * @var DiffieHellman|null
     */
    private $dh;

    /**
     * MicroServiceReactor constructor.
     *
     * @param string $ip
     * @param int $port
     * @param string $route
     * @param string $apiKey
     * @param null|string $algorithm
     *
     * @throws EncryptException
     */
    public function __construct(string $ip, int $port, string $route, string $apiKey, ?string $algorithm)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->route = $route;
        $this->apiKey = $apiKey;
        $this->algorithm = (empty($algorithm) || strtolower($algorithm) === "null") ? null : $algorithm;

        if (null === $this->algorithm) {
            $this->encrypt = new DummyEncrypt();
        } else {
            $this->encrypt = new OpenSSLEncrypt($algorithm);
            $this->dh = new DiffieHellman(true);
        }

        $this->setLoggerClosure([new Logger(), 'log']);
    }

    /**
     * @throws CallbackException
     * @throws ServiceException
     * @throws SocketException
     * @throws DiffieHellmanException
     * @throws EncryptException
     */
    public function process()
    {
        if (null === $this->controllerClosure) {
            throw new CallbackException("You mast to set 'setController' function");
        }

        $this->create();
        $this->connect();
        $this->register();
        $this->listen();
    }

    /**
     * @throws SocketException
     */
    private function create(): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (false === $socket) {
            throw new SocketException("Can`t create socket");
        }

        $this->socket = $socket;

    }

    /**
     * @throws SocketException
     */
    private function connect(): void
    {
        $isConnect = socket_connect($this->socket, $this->ip, $this->port);

        if (false === $isConnect) {
            throw new SocketException("Can`t connect socket");
        }
    }

    /**
     * @throws ServiceException
     * @throws DiffieHellmanException
     * @throws EncryptException
     *
     * {"route":"passport","payload":{"clientId":"b050fa90-1ca1-11e8-ac3c-7e74fdd684e4"}}
     */
    private function register(): void
    {
        ($this->loggerClosure)("Service register", ['route' => $this->route], 0);

        $request = new Request('system');
        $request->addPayload('action', 'register');
        $request->addPayload('route', $this->route);

        if (null !== $this->algorithm) {
            $request->addPayload('p', $this->dh->getPrime());
            $request->addPayload('g', $this->dh->getGenerator());
            $request->addPayload('public_key', $this->dh->getPublicKey());
        }

        $buffer = $this->prepareBuffer($request);
        $isSend = (bool)socket_write($this->socket, $buffer, mb_strlen($buffer));

        if (true !== $isSend) {
            throw new ServiceException("Can`t send register request to service");
        }

        $result = socket_read($this->socket, 1048 * 2, PHP_NORMAL_READ);
        $result = json_decode(trim($result));

        if (null === $result) {
            throw new ServiceException("Can`t decode register response");
        }

        if ($result->status !== 'OK') {
            throw new ServiceException("Can`t register on service");
        }

        if (null !== $this->algorithm) {
            $this->encrypt->setPassword($this->dh->getSharedKey($result->payload->public_key));
        }

        ($this->loggerClosure)("Service registered", ['route' => $this->route], 0);
    }

    /**
     * @throws SocketException
     * @throws EncryptException
     */
    private function listen(): void
    {
        while (true) {
            $isConnect = socket_recv($this->socket, $result, 1048 * 8, MSG_DONTWAIT);

            if (0 === $isConnect) {
                throw new SocketException('Can`t read data from socket');
            }

            ($this->loggerClosure)("Service receive data {$result}", ['route' => $this->route], 0);
            $result =  trim($result);

            if (!empty($result)) {
                $processedData = ($this->controllerClosure)($result);

                $request = new Request($this->route);
                $request->addPayload('data', $processedData);
                $request->addPayload('apiKey', $this->apiKey);
                $request = $this->encrypt->encrypt($request);

                ($this->loggerClosure)("Service send data {$processedData}", ['route' => $this->route], 100);
                $buffer = $this->prepareBuffer($request);
                socket_write($this->socket, $buffer, mb_strlen($buffer));
                $result = null;
            }

            usleep(self::SLEEP_TIME);
        };
    }

    /**
     * @param \Closure[] $controllerClosure
     */
    public function setControllerClosure(array $controllerClosure): void
    {
        $this->controllerClosure = $controllerClosure;
    }

    /**
     * @param \Closure[] $loggerClosure
     */
    public function setLoggerClosure(array $loggerClosure): void
    {
        $this->loggerClosure = $loggerClosure;
    }

    /**
     * @param string $buffer
     * @return string
     */
    private function prepareBuffer(string $buffer): string
    {
        return $buffer."\r\n";
    }
}