<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

interface StatusUpdatable
{
    /**
     * @param string $uuid It is Transaction Id on Magento side
     * @param bool $useAttempt
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function updateStatus($uuid, $useAttempt = false);
}
