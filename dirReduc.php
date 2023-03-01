<?php
/**
 * this is kata solution for codewars
 * Directions Reduction
 * https://www.codewars.com/kata/550f22f4d758534c1100025a
 * Goal is to reduce redundant steps from given path
 * I'm not completely happy with that map as I think there could be faster solution, but I didn't want to waste time with tests
 */

function dirReduc($arr) {
    return array_reduce($arr, function($path, $direction) {
        end($path) === opposite($direction) ? array_pop($path) : $path[] = $direction;
        return $path;
    }, []);
}

function opposite($direction) {
    switch ($direction) {
        case 'NORTH': return 'SOUTH';
        case 'SOUTH': return 'NORTH';
        case 'WEST': return 'EAST';
        case 'EAST': return 'WEST';
    }
}
