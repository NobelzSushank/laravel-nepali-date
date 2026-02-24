<?php

namespace NobelzSushank\Bsad\Support;

class Locale
{
    public const EN = 'en';
    public const NP = 'np';

    /**
     * Get the names of the BS months in the specified locale.
     *
     * @param string $locale
     *
     * @return array
     */
    public static function bsMonths(string $locale): array
    {
        if ($locale === self::NP) {
            return ['बैशाख', 'जेठ', 'असार', 'श्रावण', 'भदौ', 'आश्विन', 'कार्तिक', 'मंसिर', 'पौष', 'माघ', 'फाल्गुन', 'चैत्र'];
        }

        return ['Baisakh', 'Jestha', 'Ashadh', 'Shrawan', 'Bhadra', 'Ashwin', 'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra'];
    }

    /**
     * Get the names of the weekdays in the specified locale.
     *
     * @param string $locale
     *
     * @return array
     */
    public static function weekdays(string $locale): array
    {
        if ($locale === self::NP) {
            return ['आइतवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'बिहिवार', 'शुक्रवार', 'शनिवार'];
        }

        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    }

    /**
     * Get the names of the AD months in the specified locale.
     *
     * @param string $locale
     *
     * @return array
     */
    public static function adMonths(string $locale): array
    {
        if ($locale === self::NP) {
            return ['जनवरी', 'फेब्रुअरी', 'मार्च', 'अप्रिल', 'मे', 'जुन', 'जुलाई', 'अगस्ट', 'सेप्टेम्बर', 'अक्टोबर', 'नोभेम्बर', 'डिसेम्बर'];
        }

        return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    }


}