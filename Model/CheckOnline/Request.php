<?php


namespace Mygento\Kkm\Model\CheckOnline;


use Mygento\Kkm\Api\Data\ItemInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

class Request extends \Mygento\Kkm\Model\Request\Request
{
    const CHECKONLINE_SELL_OPERATION_TYPE = 0;

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
     * @var string
     */
    private $place;

    /**
     * @var string
     */
    private $entityStoreId;

    /**
     * @inheritDoc
     */
    public function getSno()
    {
        return (int) $this->sno;
    }

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
     * @return string|null
     */
    public function getClientId()
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

    /**
     * @return string
     */
    public function getPlace(): string
    {
        return $this->place;
    }

    /**
     * @param string $place
     * @return RequestInterface
     */
    public function setPlace($place): RequestInterface
    {
        $this->place = $place;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityStoreId(): string
    {
        return $this->entityStoreId;
    }

    /**
     * @param string $storeId
     * @return RequestInterface
     */
    public function setEntityStoreId($storeId): RequestInterface
    {
        $this->entityStoreId = $storeId;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'Device' => $this->getDevice(),
            'Password' => $this->getPassword(),
            'RequestId' => $this->getExternalId(),
            'DocumentType' => $this->getOperationType(),
            'Lines' => $this->getItems(),
            'NonCash' => $this->getNonCash(),
            'TaxMode' => $this->getSno(),
            'PhoneOrEmail' => $this->getPhone() ?: $this->getEmail(),
            'Place' => $this->getPlace(),
        ];

        if ($this->getClientId()) {
            $data['ClientId'] = $this->getClientId();
        }

        return $data;
    }
}
