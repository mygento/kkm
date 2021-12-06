<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Resend\Validator;

use Magento\Framework\AuthorizationInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Exception\ResendAvailabilityException;
use Mygento\Kkm\Model\Resend\ValidatorInterface;

class Permission implements ValidatorInterface
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws ResendAvailabilityException
     */
    public function validate($entity)
    {
        if (!$this->authorization->isAllowed('Mygento_Kkm::cheque_resend')) {
            throw new ResendAvailabilityException(
                __('You don\'t have a permission to resend cheques', $entity->getIncrementId())
            );
        }
    }
}
