<?php


namespace Mygento\Kkm\Model\CheckOnline;


use Mygento\Kkm\Api\Data\ItemInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

class Request extends \Mygento\Kkm\Model\Request\Request
{
    const REQUEST_ID_KEY = 'RequestId';

    const CHECKONLINE_OPERATION_TYPE_MAPPING = [
        RequestInterface::SELL_OPERATION_TYPE => 0,
        RequestInterface::REFUND_OPERATION_TYPE => 2,
    ];

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
    private $group;

    /**
     * @var bool
     */
    private $fullResponse;

    /**
     * @var string
     */
    private $entityType;

    private $type;

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
     * @return string|int
     */
    public function getEntityStoreId()
    {
        return $this->entityStoreId;
    }

    /**
     * @param string|int $storeId
     * @return RequestInterface
     */
    public function setEntityStoreId($storeId): RequestInterface
    {
        $this->entityStoreId = $storeId;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     * @return RequestInterface
     */
    public function setGroup($group): RequestInterface
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFullResponse(): bool
    {
        return $this->fullResponse;
    }

    /**
     * @param bool $fullResponse
     * @return RequestInterface
     */
    public function setFullResponse($fullResponse): RequestInterface
    {
        $this->fullResponse = $fullResponse;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @param $entityType
     * @return RequestInterface
     */
    public function setEntityType($entityType): RequestInterface
    {
        $this->entityType = $entityType;

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
            'DocumentType' => self::CHECKONLINE_OPERATION_TYPE_MAPPING[$this->getOperationType()],
            'Lines' => $this->getItems(),
            'NonCash' => $this->getNonCash(),
            'TaxMode' => $this->getSno(),
            'PhoneOrEmail' => $this->getPhone() ?: $this->getEmail(),
            'Place' => $this->getPlace(),
            'FullResponse' => $this->getFullResponse(),
        ];

        if ($this->getClientId()) {
            $data['ClientId'] = $this->getClientId();
        }

        if ($this->getGroup()) {
            $data['Group'] = $this->getGroup();
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function __serialize(): array
    {
        return [
            'sno' => $this->sno,
            'externalId' => $this->externalId,
            'email' => $this->email,
            'clientName' => $this->clientName,
            'clientInn' => $this->clientInn,
            'companyEmail' => $this->companyEmail,
            'phone' => $this->phone,
            'items' => $this->items,
            'payments' => $this->payments,
            'total' => $this->total,
            'inn' => $this->inn,
            'paymentAddress' => $this->paymentAddress,
            'callbackUrl' => $this->callbackUrl,
            'operationType' => $this->operationType,
            'salesEntityId' => $this->salesEntityId,
            'retryCount' => $this->retryCount,
            'additionalUserProps' => $this->additionalUserProps,
            'additionalCheckProps' => $this->additionalCheckProps,
            'entityStoreId' => $this->entityStoreId,
            'device' => $this->device,
            'password' => $this->password,
            'clientId' => $this->clientId,
            'nonCash' => $this->nonCash,
            'place' => $this->place,
            'group' => $this->group,
            'fullResponse' => $this->fullResponse,
            'entityType' => $this->entityType,
            'type' => $this->type,
        ];
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->sno = $data['sno'];
        $this->externalId = $data['externalId'];
        $this->email = $data['email'];
        $this->clientName = $data['clientName'];
        $this->clientInn = $data['clientInn'];
        $this->companyEmail = $data['companyEmail'];
        $this->phone = $data['phone'];
        $this->items = $data['items'];
        $this->payments = $data['payments'];
        $this->total = $data['total'];
        $this->inn = $data['inn'];
        $this->paymentAddress = $data['paymentAddress'];
        $this->callbackUrl = $data['callbackUrl'];
        $this->operationType = $data['operationType'];
        $this->salesEntityId = $data['salesEntityId'];
        $this->retryCount = $data['retryCount'];
        $this->additionalUserProps = $data['additionalUserProps'];
        $this->additionalCheckProps = $data['additionalCheckProps'];
        $this->entityStoreId = $data['entityStoreId'];
        $this->device = $data['device'];
        $this->password = $data['password'];
        $this->clientId = $data['clientId'];
        $this->nonCash = $data['nonCash'];
        $this->place = $data['place'];
        $this->group = $data['group'];
        $this->fullResponse = $data['fullResponse'];
        $this->entityType = $data['entityType'];
        $this->type = $data['type'];
    }
}
