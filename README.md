# Relay Checkin Bundle

This Symfony bundle provides API endpoints for

- TBD

for the API-Gateway.

## Prerequisites

- API Gateway with openAPI/Swagger

## Installation

### Step 1

Copy this bundle to `./bundles/relay-checkin-bundle`

### Step 2

Enable this bundle in `./config/bundles.php` by adding this element to the array returned:

```php
...
    return [
        ...
        Dbp\Relay\CheckinBundle\DbpRelayCheckinBundle::class => ['all' => true],
    ];
}
```

### Step 3

Add this bundle to `./symfony.lock`:

```json
...
    "dbp/relay-checkin-bundle": {
        "version": "dev-master"
    },
...
```

## Roles

This bundle needs the roles `ROLE_SCOPE_LOCATION-CHECK-IN` and `ROLE_SCOPE_LOCATION-CHECK-IN-GUEST` assigned to the user
to get permissions for the api.

