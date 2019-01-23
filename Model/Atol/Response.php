<?php

namespace Mygento\Kkm\Model\Atol;


class Response
{
    public const STATUS_DONE = 'done';
    public const STATUS_FAIL = 'fail';
    public const STATUS_WAIT = 'wait';

    /**
     * @var string
     */
    private $uuid;
    private $error;
    /**
     * @var string
     */
    private $status;
    private $payload;
    /**
     * @var string
     */
    private $timestamp;
    /**
     * @var string
     */
    private $groupCode;
    /**
     * @var string
     */
    private $daemonCode;
    /**
     * @var string
     */
    private $deviceCode;
    /**
     * @var string
     */
    private $callbackUrl;
    /**
     * @var string json with raw ATOL response
     */
    private $jsonResponse;

    /**
     * ReportResponse constructor.
     * @param string $jsonRaw
     * @throws \Exception
     */
    public function __construct($jsonRaw)
    {
        $json = json_decode($jsonRaw);
        if (!$json) {
            throw new \Exception(
                __('Response from Atol is not valid. Response: %1', (string)$jsonRaw)
            );
        }

        $this->uuid = $json->uuid ?? null;
        $this->error = $json->error ?? null;
        $this->payload = $json->payload ?? null;
        $this->status = $json->status;
        $this->timestamp = $json->timestamp;
        $this->groupCode = $json->group_code ?? null;
        $this->daemonCode = $json->daemon_code ?? null;
        $this->deviceCode = $json->device_code ?? null;
        $this->callbackUrl = $json->callback_url ?? null;
        $this->jsonResponse = json_encode($json);
    }

    /**
     * @return string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return null|object
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return null|string
     */
    public function getErrorMessage(): ?string
    {
        if (!$this->error) {
            return null;
        }

        $message = $this->getErrorText();
        $message .= '. Code: '.$this->getErrorCode();
        $message .= '. Error Id: '.$this->getErrorId();
        $message .= '. Error type: '.$this->getErrorType();

        return $message;
    }

    public function getMessage()
    {
        $message = 'Status: ';
        $message .= ucfirst($this->getStatus());
        $message .= ' Uuid: ';
        $message .= ($this->getUuid() ?? 'no uuid').' ';
        $message .= $this->getErrorMessage() ?? '';

        return $message;
    }

    /**
     * @return null|string
     */
    public function getErrorId(): ?string
    {
        return $this->error->error_id ?? null;
    }

    /**
     * @return null|string
     */
    public function getErrorText(): ?string
    {
        return $this->error->text ?? null;
    }

    /**
     * @return null|string
     */
    public function getErrorCode(): ?string
    {
        return $this->error->code ?? null;
    }

    /**
     * @return null|string
     */
    public function getErrorType(): ?string
    {
        return $this->error->type ?? null;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return null
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getGroupCode(): ?string
    {
        return $this->groupCode;
    }

    /**
     * @return string
     */
    public function getDaemonCode(): ?string
    {
        return $this->daemonCode;
    }

    /**
     * @return string
     */
    public function getDeviceCode(): ?string
    {
        return $this->deviceCode;
    }

    /**
     * @return string
     */
    public function getCallbackUrl(): ?string
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
     * @return bool
     */
    public function isDone()
    {
        return $this->getStatus() === self::STATUS_DONE;
    }

    public function isFailed()
    {
        return $this->getStatus() === self::STATUS_FAIL;
    }

    public function isWait()
    {
        return $this->getStatus() === self::STATUS_WAIT;
    }

    public function __toString()
    {
        return $this->getJsonResponse();
    }
}

