# DbpRelayCheckinBundle

[GitLab](https://gitlab.tugraz.at/dbp/check-in/dbp-relay-checkin-bundle) |
[Packagist](https://packagist.org/packages/dbp/relay-checkin-bundle) |
[Frontend Application](https://gitlab.tugraz.at/dbp/check-in/checkin) |
[Check-in Website](https://dbp-demo.tugraz.at/site/software/check-in.html)

This bundle handles check-ins to places and contact tracing for warning about COVID-19 cases.

You will need to install and set up the Digital Blueprint fork of [CampusQR](https://gitlab.tugraz.at/dbp/check-in/campus-qr),
the open source system for contact tracing at universities.

See [Check-in Website](https://dbp-demo.tugraz.at/site/software/check-in.html) for more information.

There is a corresponding frontend application that uses this API at [Check-in Frontend Application](https://gitlab.tugraz.at/dbp/check-in/checkin).

## Bundle installation

You can install the bundle directly from [packagist.org](https://packagist.org/packages/dbp/relay-checkin-bundle).

```bash
composer require dbp/relay-checkin-bundle
```

## Integration into the Relay API Server

* Add the bundle to your `config/bundles.php` in front of `DbpRelayCoreBundle`:

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

If you were using the [DBP API Server Template](https://gitlab.tugraz.at/dbp/relay/dbp-relay-server-template)
as template for your Symfony application, then the configuration file should have already been generated for you.

To handle locking you need to [configure locking in the core bundle](https://gitlab.tugraz.at/dbp/relay/dbp-relay-core-bundle#bundle-config). 

You also need to [configure the Symfony Messenger in the core bundle](https://gitlab.tugraz.at/dbp/relay/dbp-relay-core-bundle#bundle-config) to check out guests after a certain amount of time.

For more info on bundle configuration see <https://symfony.com/doc/current/bundles/configuration.html>.

## Roles

This bundle needs the roles `ROLE_SCOPE_LOCATION-CHECK-IN` and `ROLE_SCOPE_LOCATION-CHECK-IN-GUEST` assigned to the user
to get permissions for the api.

