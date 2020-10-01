<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Service;

use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

class LocationCheckInUrlApi
{
    /**
     * @param string $campusQRUrl
     * @param string $location
     * @return string
     * @throws UriException
     */
    public function getLocationRequestUrl(string $campusQRUrl, string $location): string
    {
        $uriTemplate = new UriTemplate('/location/{location}/visit');

        return $campusQRUrl . $uriTemplate->expand([
            'location' => $location,
        ]);
    }
}
