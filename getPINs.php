<?php
/**
 * this is kata solution for codewars
 * The observed PIN
 * https://www.codewars.com/kata/5263c6999e0f40dee200059d
 * Main goal is to list all possible combinations on pressed keys
 * Ideal solution would be some while, but I used recursion as I wanted to try if it will work
 */

function getPINs($observed) {
    // Create map manually is faster than computing it.
    // Also this map is based on common knowledge, which can't be computed correctly
    $map = [
        '0' => ['0', '8'],
        '1' => ['1', '2', '4'],
        '2' => ['1', '2', '3', '5'],
        '3' => ['2', '3', '6'],
        '4' => ['1', '4', '5', '7'],
        '5' => ['2', '4', '5', '6', '8'],
        '6' => ['3', '5', '6', '9'],
        '7' => ['4', '7', '8'],
        '8' => ['5', '7', '8', '9', '0'],
        '9' => ['6', '8', '9'],
    ];

    $array = [];
    foreach (str_split($observed) as $num) {
        $array[] = $map[$num];
    }

    return getCombinations($array);
}

function getCombinations(array $keys, int $i = 0): array
{
    if (!$keys[$i]) {
        return [];
    }
    if (count($keys) === $i + 1) {
        return $keys[$i];
    }

    $temp = getCombinations($keys, $i + 1);
    $result = [];

    foreach ($keys[$i] as $key) {
        foreach ($temp as $tKey) {
            $result[] = $key . $tKey;
        }
    }

    return $result;
}
