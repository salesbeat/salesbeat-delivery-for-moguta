<?php

namespace Salesbeat;

new Tools;

class Tools
{
    /**
     * @param array $array
     */
    public static function printr($array = [])
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    }

    /**
     * @param array $array
     */
    public static function vardump($array = [])
    {
        echo '<pre>';
        var_dump($array);
        echo '</pre>';
    }

    /**
     * @param string $phone
     * @return string
     */
    public static function phoneToTel($phone = '')
    {
        if ($phone) $phone = preg_replace('/[^+0-9]+/', '', $phone);
        return $phone;
    }

    /**
     * @param int $number
     * @param array $suffix
     * @return string
     */
    public static function suffixToNumber($number = 0, $suffix = [])
    {
        $keys = [2, 0, 1, 1, 1, 2];
        $mod = $number % 100;
        $suffixKey = ($mod > 7 && $mod < 20) ? 2 : $keys[min($mod % 10, 5)];
        return $number . ' ' . $suffix[$suffixKey];
    }
}