<?php
/**
 * this is kata solution for codewars
 * Square into Squares. Protect trees!
 * https://www.codewars.com/kata/54eb33e5bc1a25440d000891
 * Main goal is to decompose n2 into strictly increasing sequence of numbers, which sum of squares equals n2
 * I, once again, used recursion as it seem as best solution in here
 */

class Sq2Squares
{
    public static function decompose($number, $remaining = null, $array = []) {
        if ($remaining === null) $remaining = $number * $number;
        $start = min($number - 1, floor(sqrt($remaining))); // Start number should not be higher than square root of remaining
        var_dump($start);
        for ($i = $start; $i > 0; $i--) {
            $temp = $array;
            array_unshift($temp, $i);
            $rem = $remaining - ($i * $i);

            if ($rem == 0) return $temp;
            if ($result = self::decompose($i, $rem, $temp)) return $result;
        }

        return null;
    }
}
