<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\ResponseInterface;

class Response implements ResponseInterface
{
    // phpcs:disable
    private $uuid;

    private $error;

    private $status;

    private $payload;

    private $externalId;

    private $timestamp;

    private $groupCode;

    private $daemonCode;

    private $deviceCode;

    private $callbackUrl;

    // phpcs:enable

    /**
     * @var string json with raw ATOL response
     */
    private $jsonResponse;

    /**
     * @param string $jsonRaw
     * @throws \Exception
     */
    public function __construct($jsonRaw)
    {
        $json = json_decode($jsonRaw);
        if (!$json) {
            throw new \Exception(
                __('Response from Atol is not valid. Response: %1', (string) $jsonRaw)
            );
        }
        // phpcs:disable
        $this->uuid = $json->uuid ?? null;
        $this->error = $json->error ?? null;
        $this->payload = $json->payload ?? null;
        $this->status = $json->status ?? null;
        $this->externalId = $json->external_id ?? null;
        $this->timestamp = $json->timestamp;
        $this->groupCode = $json->group_code ?? null;
        $this->daemonCode = $json->daemon_code ?? null;
        $this->deviceCode = $json->device_code ?? null;
        $this->callbackUrl = $json->callback_url ?? null;
        $this->jsonResponse = json_encode($json);
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
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return object|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage()
    {
        if (!$this->error) {
            return null;
        }

        $message = $this->getErrorText();
        $message .= '. Code: ' . $this->getErrorCode();
        $message .= '. Error Id: ' . $this->getErrorId();
        $message .= '. Error type: ' . $this->getErrorType();

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        $message = 'Status: ';
        $message .= ucfirst($this->getStatus());
        $message .= ' Uuid: ';
        $message .= ($this->getUuid() ?? 'no uuid') . ' ';
        $message .= $this->getErrorMessage() ?? '';

        return $message;
    }

    /**
     * @return string|null
     */
    public function getErrorId()
    {
        return $this->error->error_id ?? null;
    }

    /**
     * @return string|null
     */
    public function getErrorText()
    {
        return $this->error->text ?? null;
    }

    /**
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->error->code ?? null;
    }

    /**
     * @return string|null
     */
    public function getErrorType()
    {
        return $this->error->type ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @return null
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getGroupCode()
    {
        return $this->groupCode;
    }

    /**
     * @return string
     */
    public function getDaemonCode()
    {
        return $this->daemonCode;
    }

    /**
     * @return string
     */
    public function getDeviceCode()
    {
        return $this->deviceCode;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
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
}
