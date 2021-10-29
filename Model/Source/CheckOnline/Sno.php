<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source\CheckOnline;

use Mygento\Kkm\Model\Source\AbstractSno;

class Sno extends AbstractSno
{
    protected const RECEIPT_SNO_OSN = 1;
    protected const RECEIPT_SNO_USN_INCOME = 2;
    protected const RECEIPT_SNO_USN_INCOME_OUTCOME = 4;
    protected const RECEIPT_SNO_ENVD = 8;
    protected const RECEIPT_SNO_ESN = 16;
    protected const RECEIPT_SNO_PATENT = 32;
}
