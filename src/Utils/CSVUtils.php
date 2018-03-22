<?php

namespace CSVAPI\Utils;

/**
 * Class CSVUtils
 * @package CSVAPI\Utils
 */
class CSVUtils
{
    /**
     * Normalize all    line endings to Unix line endings
     * @param $string   the mixed-ending string to normalized
     * @return string   the normalized string
     */
    public static function normalizeLineEndings($string) {
        $search = ["\r\n", "\r"];
        $replace = array_fill(0, count($search), "\n");
        return str_replace($search, $replace, $string);
    }
}


