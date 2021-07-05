<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Frontend;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

class Callback extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    private $errorHelper;

    /**
     * @var \Mygento\Kkm\Helper\Resell
     */
    private $resellHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\SendInterface
     */
    private $processor;

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * Callback constructor.
     * @param \Mygento\Kkm\Model\Atol\ResponseFactory $responseFactory
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Resell $resellHelper
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Mygento\Kkm\Model\Atol\ResponseFactory $responseFactory,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Resell $resellHelper,
        \Mygento\Kkm\Api\Processor\SendInterface $processor,
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->responseFactory = $responseFactory;
        $this->kkmHelper = $kkmHelper;
        $this->vendor = $vendor;
        $this->errorHelper = $errorHelper;
        $this->resellHelper = $resellHelper;
        $this->processor = $processor;
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

            //Если был совершен refund по инвойсу - следовательно, это коррекция чека
            //и нужно заново отправить инвойс в АТОЛ
            if ($this->resellHelper->isNeededToResendSell($entity, $response)) {
                $this->processor->proceedResellSell($entity);
            }

            $this->getResponse()
                ->setBody((string) $entity->getIncrementId())
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

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
