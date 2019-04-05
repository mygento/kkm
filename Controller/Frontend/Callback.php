<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Frontend;

use Magento\Framework\Controller\ResultFactory;

class Callback extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Mygento\Kkm\Model\Atol\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    private $errorHelper;

    /**
     * Callback constructor.
     * @param \Mygento\Kkm\Model\Atol\ResponseFactory $responseFactory
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Mygento\Kkm\Model\Atol\ResponseFactory $responseFactory,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->responseFactory = $responseFactory;
        $this->kkmHelper = $kkmHelper;
        $this->vendor = $vendor;
        $this->errorHelper = $errorHelper;
    }

    /**
     * Execute
     */
    public function execute()
    {
        // @codingStandardsIgnoreStart
        $json = file_get_contents('php://input');
        // @codingStandardsIgnoreStop
        $entity = null;

        //For testing purposes
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        try {
            $response = $this->responseFactory->create(['jsonRaw' => $json]);

            $this->kkmHelper->info(
                __(
                    'Callback received. Status: %1. Uuid: %2',
                    $response->getStatus(),
                    $response->getUuid()
                )
            );
            $this->kkmHelper->debug(__('Callback received: %1', (string) $response));

            //Sometimes callback is received when transaction is not saved yet. In order to avoid this
            sleep(3);

            $entity = $this->vendor->saveCallback($response);

            $this->getResponse()
                ->setBody($entity->getIncrementId())
                ->sendResponse();

            $result->setContents($entity->getIncrementId());
        } catch (\Throwable $exc) {
            $this->kkmHelper->error($exc->getMessage());
            $this->kkmHelper->debug("Callback RAW: {$json}");

            if ($entity && $entity->getId()) {
                $this->errorHelper->processKkmChequeRegistrationError($entity, $exc);
            }

            $result->setContents($exc->getMessage());
        }

        return $result;
    }
}
