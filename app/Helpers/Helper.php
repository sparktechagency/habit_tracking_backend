<?php

if (!function_exists('format_price')) {
    function format_price($price)
    {
        return number_format($price, 2) . ' ৳';
    }
}

if (!function_exists('generate_otp')) {
    function generate_otp($length = 6)
    {
        return rand(pow(10, $length - 1), pow(10, $length) - 1);
    }
}
