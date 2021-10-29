<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source\Atol;

use Mygento\Kkm\Model\Source\AbstractSno;

class Sno extends AbstractSno
{
    protected const RECEIPT_SNO_OSN = 'osn';
    protected const RECEIPT_SNO_USN_INCOME = 'usn_income';
    protected const RECEIPT_SNO_USN_INCOME_OUTCOME = 'usn_income_outcome';
    protected const RECEIPT_SNO_ENVD = 'envd';
    protected const RECEIPT_SNO_ESN = 'esn';
    protected const RECEIPT_SNO_PATENT = 'patent';
}
