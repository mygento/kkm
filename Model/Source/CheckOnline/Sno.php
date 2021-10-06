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
    // phpcs:disable
    protected $osnValue = 1;

    protected $usnIncomeValue = 2;

    protected $usnIncomeOutcomeValue = 4;

    protected $envdValue = 8;

    protected $esnValue = 16;

    protected $patentValue = 32;

    // phpcs:enable
}
