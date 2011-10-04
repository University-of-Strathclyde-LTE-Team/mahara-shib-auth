<?php

/**
 * Extract from $array1 all the key value pairs whe the key is part of the $keys array.
 *  
 * @param array $array
 * @param array $keys
 */
function array_extract($array, $keys) {
    $result = array();
    foreach ($keys as $key) {
        $result[$key] = $array[$key];
    }
    return $result;
}

?>