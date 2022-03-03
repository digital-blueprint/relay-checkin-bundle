<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\Service;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;

class HealthCheck implements CheckInterface
{
    private $api;

    public function __construct(CheckinApi $api)
    {
        $this->api = $api;
    }

    public function getName(): string
    {
        return 'checkin';
    }

    public function checkConnection(): CheckResult
    {
        $result = new CheckResult('Check if CampusQR is reachable');
        try {
            $this->api->checkConnection();
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage());

            return $result;
        }

        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    public function checkApi(): CheckResult
    {
        $result = new CheckResult('Check if we can use the CampusQR API');
        try {
            $this->api->checkApi();
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage());

            return $result;
        }

        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    public function check(CheckOptions $options): array
    {
        return [
            $this->checkConnection(),
            $this->checkApi(),
        ];
    }
}
