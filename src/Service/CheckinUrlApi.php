<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Service;

use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

class CheckinUrlApi
{
    /**
     * @param string $campusQRUrl
     * @param string $location
     * @param int|null $seatNumber
     *
     * @return string
     *
     * @throws UriException
     */
    public function getCheckInRequestUrl(string $campusQRUrl, string $location, ?int $seatNumber = null): string
    {
        $uriTemplate = new UriTemplate(
            $seatNumber === null ?
                '/location/{location}/visit' :
                '/location/{location}-{seatNumber}/visit');

        return $campusQRUrl.$uriTemplate->expand([
            'location' => $location,
            'seatNumber' => $seatNumber,
        ]);
    }

    /**
     * @param string $campusQRUrl
     * @param string $location
     * @param int|null $seatNumber
     *
     * @return string
     *
     * @throws UriException
     */
    public function getGuestCheckInRequestUrl(string $campusQRUrl, string $location, ?int $seatNumber = null): string
    {
        $uriTemplate = new UriTemplate(
            $seatNumber === null ?
                '/location/{location}/guestCheckinBy' :
                '/location/{location}-{seatNumber}/guestCheckinBy');

        return $campusQRUrl.$uriTemplate->expand([
            'location' => $location,
            'seatNumber' => $seatNumber,
        ]);
    }

    /**
     * @param string $campusQRUrl
     * @param string $location
     * @param int|null $seatNumber
     *
     * @return string
     *
     * @throws UriException
     */
    public function getCheckOutRequestUrl(string $campusQRUrl, string $location, ?int $seatNumber = null): string
    {
        $uriTemplate = new UriTemplate(
            $seatNumber === null ?
                '/location/{location}/checkoutSeat' :
                '/location/{location}-{seatNumber}/checkoutSeat');

        return $campusQRUrl.$uriTemplate->expand([
            'location' => $location,
            'seatNumber' => $seatNumber,
        ]);
    }

    /**
     * @param string $campusQRUrl
     *
     * @return string
     *
     * @throws UriException
     */
    public function getLocationListRequestUrl(string $campusQRUrl): string
    {
        $uriTemplate = new UriTemplate('/location/list');

        return $campusQRUrl.$uriTemplate->expand();
    }

    /**
     * @param string $campusQRUrl
     *
     * @return string
     *
     * @throws UriException
     */
    public function getCheckInActionListOfCurrentPersonRequestUrl(string $campusQRUrl): string
    {
        $uriTemplate = new UriTemplate('/report/listActiveCheckins');

        return $campusQRUrl.$uriTemplate->expand();
    }

    /**
     * @param string $campusQRUrl
     * @param string $configKey
     *
     * @return string
     *
     * @throws UriException
     */
    public function getConfigUrl(string $campusQRUrl, string $configKey): string
    {
        $uriTemplate = new UriTemplate('/config/get?id={configKey}');

        return $campusQRUrl.$uriTemplate->expand([
                'configKey' => $configKey,
            ]);
    }
}
