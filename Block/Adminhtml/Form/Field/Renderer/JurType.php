<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Block\Adminhtml\Form\Field\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Mygento\Kkm\Model\Source\JurType as JurTypeSource;

class JurType extends Select
{
    /**
     * @var JurTypeSource
     */
    private $source;

    /**
     * Constructor
     *
     * @param JurTypeSource $source
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        JurTypeSource $source,
        Context $context,
        array $data = []
    ) {
        $this->source = $source;

        parent::__construct($context, $data);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->source->toOptionArray() as $type) {
                if (isset($type['label']) && $type['label'] && $type['value']) {
                    $this->addOption($type['value'], $type['label']);
                }
            }
        }

        return parent::_toHtml();
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getInputId();
    }
}
