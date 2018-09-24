<?php
/**
 * Created by PhpStorm.
 * User: zogxray
 * Date: 20.09.18
 * Time: 16:01
 */

namespace Micseres\MicroServiceReactor\SystemRequest;

/**
 * Class Request
 * @package Micseres\MicroServiceReactor\SystemRequest
 */
class Request implements \JsonSerializable
{
    /** @var string */
    protected $route;

    /** @var array */
    protected $payload = [];

    /**
     * Request constructor.
     * @param string $route
     */
    public function __construct(string $route)
    {
        $this->route = $route;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addPayload(string $key, string $value): void
    {
        $this->payload[$key] = $value;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'route' => $this->getRoute(),
            'payload' => $this->getPayload(),
        ];
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this);
    }
}