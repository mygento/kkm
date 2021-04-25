<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface ResponseInterface
{
    const STATUS_DONE = 'done';
    const STATUS_FAIL = 'fail';
    const STATUS_WAIT = 'wait';

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return string
     */
    public function getIdForTransaction();

    /**
     * @return string|null
     */
    public function getErrorMessage();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return string|null
     */
    public function getTimestamp();

    /**
     * @return bool
     */
    public function isDone();

    /**
     * @return bool
     */
    public function isFailed();

    /**
     * @return bool
     */
    public function isWait();

    /**
     * @return array
     */
    public function getVendorSpecificTxnData();

    /**
     * @return string
     */
    public function getRawResponse();
}
