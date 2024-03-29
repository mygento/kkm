<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Helper\Transaction;

/**
 * Class Response
 * @package Mygento\Kkm\Model\CheckOnline
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Response implements ResponseInterface
{
    public const NON_FATAL_DEVICE_ERROR_CODES = [
        17, //Нет транспортного соединения с ОФД
        80, //Данные печатаются
        200, //Тайм-аут принтера
        249, //Ошибка транспортного уровня при получении данных из архива ФН
        250, //Основная плата устройства не отвечает
        253, //Прочие ошибки принтера
        254, //Принтер в оффлайне
    ];

    // phpcs:disable

    private $fceError;
    private $errorDescription;
    private $fatal;
    private $logRequestId;
    private $cloudErrorTimestamp;
    private $requestId;
    private $clientId;
    private $date;
    private $device;
    private $deviceRegistrationNumber;
    private $deviceSerialNumber;
    private $docNumber;
    private $documentType;
    private $fnSerialNumber;
    private $fiscalDocNumber;
    private $fiscalSign;
    private $grandTotal;
    private $path;
    private $qr;
    private $response;
    private $responses;
    private $text;
    private $turnNumber;
    private $status;
    private $receiptLink;

    // phpcs:enable

    /**
     * @var string json with raw Checkonline response
     */
    private $jsonResponse;

    /**
     * ReportResponse constructor.
     * @param \stdClass $decodedResponse
     * @throws \Exception
     */
    public function __construct(\stdClass $decodedResponse)
    {
        // phpcs:disable
        $this->fceError = $decodedResponse->FCEError ?? null;
        $this->errorDescription = $decodedResponse->ErrorDescription ?? null;
        $this->fatal = $decodedResponse->Fatal ?? null;
        $this->logRequestId = $decodedResponse->LogRequestId ?? null;
        $this->cloudErrorTimestamp = $decodedResponse->Timestamp ?? null;
        $this->requestId = $decodedResponse->RequestId ?? null;
        $this->clientId = $decodedResponse->ClientId ?? null;
        $this->date = $decodedResponse->Date ?? null;
        $this->device = $decodedResponse->Device ?? null;
        $this->deviceRegistrationNumber = $decodedResponse->DeviceRegistrationNumber ?? null;
        $this->deviceSerialNumber = $decodedResponse->DeviceSerialNumber ?? null;
        $this->docNumber = $decodedResponse->DocNumber ?? null;
        $this->documentType = $decodedResponse->DocumentType ?? null;
        $this->fnSerialNumber = $decodedResponse->FNSerialNumber ?? null;
        $this->fiscalDocNumber = $decodedResponse->FiscalDocNumber ?? null;
        $this->fiscalSign = $decodedResponse->FiscalSign ?? null;
        $this->grandTotal = $decodedResponse->GrandTotal ?? null;
        $this->path = $decodedResponse->Path ?? null;
        $this->qr = $decodedResponse->QR ?? null;
        $this->response = $decodedResponse->Response ?? null;
        $this->responses = $decodedResponse->Responses ?? null;
        $this->text = $decodedResponse->Text ?? null;
        $this->turnNumber = $decodedResponse->TurnNumber ?? null;
        $this->jsonResponse = json_encode($decodedResponse);
        $this->status = $this->calculateStatus();
        // phpcs:enable
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->getJsonResponse();
    }

    /**
     * @inheritdoc
     */
    public function getIdForTransaction()
    {
        return $this->requestId;
    }

    /**
     * @inheritdoc
     */
    public function setIdForTransaction($idForTransaction)
    {
        return $this->setRequestId($idForTransaction);
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     * @return $this
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFceError()
    {
        return $this->fceError;
    }

    /**
     * @return string|null
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * @return bool|null
     */
    public function getFatal()
    {
        return $this->fatal;
    }

    /**
     * @return string|null
     */
    public function getLogRequestId()
    {
        return $this->logRequestId;
    }

    /**
     * @return string|null
     */
    public function getCloudErrorTimestamp()
    {
        return $this->cloudErrorTimestamp;
    }

    /**
     * @return string|null
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return object|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp()
    {
        $result = null;
        $dateTime = $this->getDate();

        if ($dateTime) {
            $date = sprintf('%s.%s.%s', $dateTime->Date->Day, $dateTime->Date->Month, $dateTime->Date->Year);
            $time = sprintf(' %s:%s:%s', $dateTime->Time->Hour, $dateTime->Time->Minute, $dateTime->Time->Second);
            $result = $date . $time;
        }

        return $result;
    }

    /**
     * @return object|null
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @return string|null
     */
    public function getDeviceRegistrationNumber()
    {
        return $this->deviceRegistrationNumber;
    }

    /**
     * @return string|null
     */
    public function getDeviceSerialNumber()
    {
        return $this->deviceSerialNumber;
    }

    /**
     * @return int|null
     */
    public function getDocNumber()
    {
        return $this->docNumber;
    }

    /**
     * @return int|null
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @return string|null
     */
    public function getFNSerialNumber()
    {
        return $this->fnSerialNumber;
    }

    /**
     * @return int|null
     */
    public function getFiscalDocNumber()
    {
        return $this->fiscalDocNumber;
    }

    /**
     * @return int|null
     */
    public function getFiscalSign()
    {
        return $this->fiscalSign;
    }

    /**
     * @return int|null
     */
    public function getGrandTotal()
    {
        return $this->grandTotal;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    public function getQr()
    {
        return $this->qr;
    }

    /**
     * @return object|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return object|null
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * @return string|null
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return int|null
     */
    public function getTurnNumber()
    {
        return $this->turnNumber;
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage()
    {
        $message = null;
        $response = $this->getResponse();
        $fceError = $this->getFceError();

        if ($fceError) {
            $message = 'Cloud Error. ';
            $message .= $this->getFatal() ? ' Fatal. ' : '';
            $message .= $this->getErrorDescription();
            $message .= '. FCEError: ' . $this->getLogRequestId();
            $message .= '. LogRequestId: ' . $this->getLogRequestId();
        }

        if ($response && $response->Error !== 0) {
            $message = 'Vendor device Error. ';
            $message .= implode('; ', $response->ErrorMessages);
            $message .= '. Code: ' . $response->Error;
        }

        return $message;
    }

    /**
     * @return string|null
     */
    public function getErrorType()
    {
        $result = null;

        if ($this->getFceError()) {
            $result = 'Cloud Error';
        }

        if ($this->getResponse() && $this->getResponse()->Error !== 0) {
            $result = 'Vendor Device Error';
        }

        return $result;
    }

    /**
     * @return int|null
     */
    public function getErrorCode()
    {
        $result = null;

        if ($this->getFceError()) {
            $result = $this->getFceError();
        }

        if ($this->getResponse() && $this->getResponse()->Error !== 0) {
            $result = $this->getResponse()->Error;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        $message = 'Status: ';
        $message .= ucfirst($this->getStatus()) . '. ';
        $message .= $this->getErrorMessage() ?? '';
        $message .= $this->getReceiptLink() ?
            sprintf(" <a href='%s'>View receipt</a>.", $this->getReceiptLink())
            : '';

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getJsonResponse()
    {
        return $this->jsonResponse;
    }

    /**
     * @inheritdoc
     */
    public function isDone()
    {
        return $this->getStatus() === self::STATUS_DONE;
    }

    /**
     * @inheritdoc
     */
    public function isFailed()
    {
        return $this->getStatus() === self::STATUS_FAIL;
    }

    /**
     * @inheritdoc
     */
    public function isWait()
    {
        return $this->getStatus() === self::STATUS_WAIT;
    }

    /**
     * return string|null
     */
    public function getReceiptLink()
    {
        return $this->receiptLink;
    }

    /**
     * @param string $receiptLink
     * @return $this
     */
    public function setReceiptLink($receiptLink)
    {
        $this->receiptLink = $receiptLink;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVendorSpecificTxnData()
    {
        $data = [
            Request::REQUEST_ID_KEY => $this->getRequestId(),
            Transaction::STATUS_KEY => $this->getStatus(),
            Transaction::ERROR_MESSAGE_KEY => $this->getErrorMessage(),
            'DeviceRegistrationNumber' => $this->getDeviceRegistrationNumber(),
            'DeviceSerialNumber' => $this->getDeviceSerialNumber(),
            'DocNumber' => $this->getDocNumber(),
            'DocumentType' => $this->getDocumentType(),
            'FNSerialNumber' => $this->getFNSerialNumber(),
            'FiscalDocNumber' => $this->getFiscalDocNumber(),
            'FiscalSign' => $this->getFiscalSign(),
            'GrandTotal' => $this->getGrandTotal(),
            'TurnNumber' => $this->getTurnNumber(),
        ];

        if ($this->getText()) {
            $data['Text'] = $this->getText();
        }

        $data[Transaction::RAW_RESPONSE_KEY] = $this->getRawResponse();

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getRawResponse()
    {
        return $this->getJsonResponse();
    }

    /**
     * @return string
     */
    private function calculateStatus()
    {
        $result = '';
        $response = $this->getResponse();
        $fceError = $this->getFceError();

        if ($response && $response->Error === 0) {
            $result = self::STATUS_DONE;
        }

        if ($fceError) {
            $result = self::STATUS_FAIL;
        }

        if ($response && $response->Error !== 0) {
            $result = in_array($response->Error, self::NON_FATAL_DEVICE_ERROR_CODES)
                ? self::STATUS_WAIT
                : self::STATUS_FAIL;
        }

        return $result;
    }
}
