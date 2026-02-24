<?php

namespace NobelzSushank\Bsad\Support;

class NepaliDigits
{
    private const MAP = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

    /**
     * Convert ASCII digits in the given string to their Nepali Unicode equivalents.
     *
     * @param string $ascii
     *
     * @return string
     */
    public static function toNepali(string $ascii): string
    {
        return strtr($ascii, self::MAP);
    }
}