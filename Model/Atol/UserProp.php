<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\UserPropInterface;

class UserProp implements UserPropInterface
{
    // phpcs:disable
    /**
     * @var string 
     */
    private $name = '';

    /**
     * @var string
     */
    private $value = '';
    // phpcs:enable

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }
}
