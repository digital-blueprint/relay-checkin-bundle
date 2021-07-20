<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Helpers;

class Tools
{
    public static function filterErrorMessage(string $message): string
    {
        // hide token parameters
        return preg_replace('/([&?]token=)[\w\d-]+/i', '${1}hidden', $message);
    }
}
