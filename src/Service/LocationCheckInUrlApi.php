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
     * @param int|null $seatNumber
     * @return string
     * @throws UriException
     */
    public function getLocationRequestUrl(string $campusQRUrl, string $location, ?int $seatNumber = null): string
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

    /**
     * @param string $campusQRUrl
     * @param string $location
     * @param int|null $seatNumber
     * @return string
     * @throws UriException
     */
    public function getCheckOutRequestUrl(string $campusQRUrl, string $location, ?int $seatNumber = null): string
    {
        $uriTemplate = new UriTemplate(
            $seatNumber === null ?
                '/location/{location}/checkout' :
                '/location/{location}-{seatNumber}/checkout');

        return $campusQRUrl . $uriTemplate->expand([
            'location' => $location,
            'seatNumber' => $seatNumber,
        ]);
    }

    /**
     * @param string $campusQRUrl
     * @return string
     * @throws UriException
     */
    public function getLocationListRequestUrl(string $campusQRUrl): string
    {
        $uriTemplate = new UriTemplate('/location/list');

        return $campusQRUrl . $uriTemplate->expand();
    }

    /**
     * @param string $campusQRUrl
     * @return string
     * @throws UriException
     */
    public function getLocationCheckInActionListOfCurrentPersonRequestUrl(string $campusQRUrl): string
    {
        $uriTemplate = new UriTemplate('/report/listActiveCheckIns');

        return $campusQRUrl . $uriTemplate->expand();
    }
}
