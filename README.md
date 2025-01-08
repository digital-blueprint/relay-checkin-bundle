# DbpRelayCheckinBundle

[GitHub](https://github.com/digital-blueprint/relay-checkin-bundle) |
[Packagist](https://packagist.org/packages/dbp/relay-checkin-bundle) |
[Frontend Application](https://github.com/digital-blueprint/checkin-app) |
[Check-in Website](https://handbook.digital-blueprint.org/blueprints/check-in)

[![Test](https://github.com/digital-blueprint/relay-checkin-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/digital-blueprint/relay-checkin-bundle/actions/workflows/test.yml)

This bundle handles check-ins to places and contact tracing for warning about COVID-19 cases.

You will need to install and set up the Digital Blueprint fork of [CampusQR](https://gitlab.tugraz.at/dbp/check-in/campus-qr),
the open source system for contact tracing at universities.

See [Check-in Website](https://handbook.digital-blueprint.org/blueprints/check-in) for more information.

There is a corresponding frontend application that uses this API at [Check-in Frontend Application](https://github.com/digital-blueprint/checkin-app).

## Bundle installation

You can install the bundle directly from [packagist.org](https://packagist.org/packages/dbp/relay-checkin-bundle).

```bash
composer require dbp/relay-checkin-bundle
```

## Configuration

For more details see the [Configuration Documentation](./docs/README.md).
