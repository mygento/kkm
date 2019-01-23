<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Block\Adminhtml\System\Config;

class GetLogs extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $urlDownload = $this->_urlBuilder->getUrl('kkm/logs/download');
        $urlClear = $this->_urlBuilder->getUrl('kkm/logs/clear');

        $button = <<<HTML
            <td class="value">
                <p><a href="{$urlDownload}">Download link</a></p>
                <p><a href="{$urlClear}">Clear logs links</a></p>
            </td>
HTML;

        return $button;
    }
}
