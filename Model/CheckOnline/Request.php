<?php


namespace Mygento\Kkm\Model\CheckOnline;


use Magento\Tests\NamingConvention\true\string;
use Mygento\Kkm\Api\Data\ItemInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

class Request extends \Mygento\Kkm\Model\Request
{
    /**
     * @var string
     */
    private $device = 'auto';

    /**
     * @var int
     */
    private $password = 1;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var int[]
     */
    private $nonCash;

    /**
     * @return string
     */
    public function getDevice(): string
    {
        return $this->device;
    }

    /**
     * @return int
     */
    public function getPassword(): int
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param $clientId
     * @return RequestInterface
     */
    public function setClientId($clientId): RequestInterface
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getNonCash(): array
    {
        return $this->nonCash;
    }

    /**
     * @param array $nonCash
     * @return RequestInterface
     */
    public function setNonCash($nonCash): RequestInterface
    {
        $this->nonCash = $nonCash;

        return $this;
    }

    public function jsonSerialize()
    {
        $data = [
            "Device" => $this->getDevice(),
            "ClientId" => $this->getClientId(),
            'Password' => $this->getPassword(),
            'RequestId' => $this->getExternalId(),
            'Lines' => $this->getItems(),
            'NonCash' => $this->getNonCash(),
            'TaxMode' => $this->getSno(),
            'PhoneOrEmail' => $this->getPhone() ?: $this->getEmail(),
        ];
    }
}
