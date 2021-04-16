<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

interface ConsumerProcessorInterface
{
    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    public function processSell($request);

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    public function processRefund($request);

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    public function processResell($request);

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface|\Mygento\Kkm\Api\Data\UpdateRequestInterface $request
     */
    public function processUpdate($request);
}
