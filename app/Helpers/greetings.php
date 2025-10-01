<?php

use Carbon\Carbon;

if (! function_exists('greetings')) {
    function greetings(): string
    {
        $hour = Carbon::now()->format('H');

        if ($hour < 18) {
            return 'Bonjour';
        }

        return 'Bonsoir';
    }
}
