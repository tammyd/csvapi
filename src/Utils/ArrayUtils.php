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

    public static function serializeArrayValues($arr){
        foreach($arr as $key=>$val){
            $arr[$key]=serialize($val);
        }

        return $arr;
    }


}