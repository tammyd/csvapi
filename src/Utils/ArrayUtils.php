<?php


namespace CSVAPI\Utils;


class ArrayUtils
{

    public static function getArrayValue($array, $key, $default = null) {
        if (isset($array[$key])) {
            return $array[$key];
        }

        return $default;
    }

}