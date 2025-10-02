<?php

use App\Models\Currency;

if (! function_exists('currency')) {
    function currency(): Currency
    {
        return Currency::default();
    }
}
