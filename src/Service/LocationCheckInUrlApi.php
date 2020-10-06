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
     * @param string|null $seatNumber
     * @return string
     * @throws UriException
     */
    public function getLocationRequestUrl(string $campusQRUrl, string $location, ?string $seatNumber = null): string
    {
        $uriTemplate = new UriTemplate(
            $seatNumber === null ?
                '/location/{location}/visit' :
                '/location/{location}-{seatNumber}/visit');

        return $campusQRUrl . $uriTemplate->expand([
            'location' => $location,
            'seatNumber' => $seatNumber,
        ]);
    }
}
