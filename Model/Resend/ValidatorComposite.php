<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Resend;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Exception\ResendAvailabilityException;

class ValidatorComposite implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators = [])
    {
        $this->validators = $validators;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws ResendAvailabilityException
     */
    public function validate($entity): bool
    {
        foreach ($this->validators as $validator) {
            $validator->validate($entity);
        }
    }
}
