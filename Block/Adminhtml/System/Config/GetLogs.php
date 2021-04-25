<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Block\Adminhtml\System\Config;

class GetLogs extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $urlDownload = $this->_urlBuilder->getUrl('kkm/logs/download');
        $urlClear = $this->_urlBuilder->getUrl('kkm/logs/clear');

        return <<<HTML
            <td class="value">
                <p><a href="{$urlDownload}">Download link</a></p>
                <p><a href="{$urlClear}">Clear logs links</a></p>
            </td>
HTML;
    }
}
