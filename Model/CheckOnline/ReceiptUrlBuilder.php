<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Helper\Data as KkmHelper;

class ReceiptUrlBuilder
{
    private const FN_SERIAL_NUMBER_VARIABLE = '{{fnSerialNumber}}';
    private const FISCAL_DOC_NUMBER_VARIABLE = '{{fiscalDocNumber}}';
    private const FISCAL_SIGN_VARIABLE = '{{fiscalSign}}';
    private const DOC_NUMBER_VARIABLE = '{{docNumber}}';
    private const SUM_VARIABLE = '{{sum}}';
    private const DATETIME_VARIABLE = '{{dateTime}}';

    /**
     * @var KkmHelper
     */
    private $kkmHelper;

    public function __construct(
        KkmHelper $kkmHelper
    ) {
        $this->kkmHelper = $kkmHelper;
    }

    /**
     * @param int|string|null $storeId
     */
    public function buildReceiptUrl(ResponseInterface $response, $storeId): string
    {
        $ofdUrl = $this->kkmHelper->getCheckonlineOfdUrl($storeId);

        if (!$ofdUrl) {
            return '';
        }

        $fnSerialNumber = $response->getFNSerialNumber();
        $fiscalDocNumber = $response->getFiscalDocNumber();
        $fiscalSign = $response->getFiscalSign();
        $docNumber = $response->getDocNumber();
        $sum = number_format(round($response->getGrandTotal() / 100, 2), 2);

        preg_match('/t=(.+?)&/', $response->getQr(), $matches);
        $dateTime = $matches[1] ?? '';

        return str_replace(
            [
                self::DATETIME_VARIABLE,
                self::FN_SERIAL_NUMBER_VARIABLE,
                self::FISCAL_DOC_NUMBER_VARIABLE,
                self::FISCAL_SIGN_VARIABLE,
                self::DOC_NUMBER_VARIABLE,
                self::SUM_VARIABLE,
            ],
            [
                $dateTime,
                $fnSerialNumber,
                $fiscalDocNumber,
                $fiscalSign,
                $docNumber,
                $sum,
            ],
            $ofdUrl
        );
    }
}
