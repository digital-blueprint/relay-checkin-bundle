# api-location-check-in-bundle

This Symfony 4.4 bundle provides API endpoints for

- TBD

for the API-Gateway.

## Prerequisites

- API Gateway with openAPI/Swagger

## Installation

### Step 1

Copy this bundle to `./bundles/api-location-check-in-bundle`

### Step 2

Enable this bundle in `./config/bundles.php` by adding this element to the array returned:

```php
...
    return [
        ...
        DBP\API\LocationCheckInBundle\DbpLocationCheckInBundle::class => ['all' => true],
    ];
}
```

### Step 3

Add this bundle to `./symfony.lock`:

```json
...
    "dbp/api-location-check-in-bundle": {
        "version": "dev-master"
    },
...
```
