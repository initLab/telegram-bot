<?php
function urlBase64Encode($str) {
    return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
}

function urlBase64Decode($str) {
    return base64_decode(strtr($str, '-_', '+/'));
}

function endswith($string, $test) {
    $length = strlen($string);
    $testLength = strlen($test);
    if ($testLength > $length) {
        return false;
    }

    return substr_compare($string, $test, $length - $testLength, $testLength) === 0;
}
