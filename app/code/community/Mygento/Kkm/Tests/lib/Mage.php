<?php

// @codingStandardsIgnoreStart
class Mage
{
    public static function helper()
    {

        $helper = new Helper();

        return $helper;
    }

    public static function dispatchEvent()
    {
        return false;
    }
}

class Helper
{
    public function __call($arg, $arg2)
    {
        return false;
    }
}
// @codingStandardsIgnoreEnd