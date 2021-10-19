# DbpRelayCheckinBundle

[GitLab](https://gitlab.tugraz.at/dbp/check-in/dbp-relay-checkin-bundle) | [Packagist](https://packagist.org/packages/dbp/relay-checkin-bundle)

This bundle handles check-ins to places and contact tracing for warning about COVID-19 cases.

You will need to install and set up the Digital Blueprint fork of [CampusQR](https://gitlab.tugraz.at/dbp/check-in/campus-qr),
the open source system for contact tracing at universities.

## Bundle installation

You can install the bundle directly from [packagist.org](https://packagist.org/packages/dbp/relay-checkin-bundle).

```bash
composer require dbp/relay-checkin-bundle
```

## Integration into the API Server

* Add the bundle to your `config/bundles.php`:

```php
...
Dbp\Relay\CheckinBundle\DbpRelayCheckinBundle::class => ['all' => true],
Dbp\Relay\CoreBundle\DbpRelayCoreBundle::class => ['all' => true],
];
```

* Run `composer install` to clear caches

## Configuration

The bundle has a `campus_qr_url` and a `campus_qr_token` configuration value that you can specify in your
app, either by hardcoding it, or by referencing an environment variable.

For this create `config/packages/dbp_relay_checkin.yaml` in the app with the following content:

```yaml
dbp_relay_checkin:
  campus_qr_url: 'https://campusqr.your.domain'
  # campus_qr_url: '%env(CAMPUS_QR_URL)%'
  campus_qr_token: 'secret token'
  # campus_qr_token: '%env(CAMPUS_QR_TOKEN)%'
```

You also need to set an environment variable `MESSENGER_TRANSPORT_DSN` in your `.env` file or by any other means.
[Redis](https://redis.io/) is also the best way for this.

Example:

```dotenv
MESSENGER_TRANSPORT_DSN=redis://redis:6379/local-messages/symfony/consumer?auto_setup=true&serializer=1&stream_max_entries=0&dbindex=0
```

For more info on bundle configuration see <https://symfony.com/doc/current/bundles/configuration.html>.

## Roles

This bundle needs the roles `ROLE_SCOPE_LOCATION-CHECK-IN` and `ROLE_SCOPE_LOCATION-CHECK-IN-GUEST` assigned to the user
to get permissions for the api.

