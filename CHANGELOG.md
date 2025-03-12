# Changelog

## Unreleased

## v1.2.16

* Update core: Add missing parent constructor calls for AbstractDataProvider children

## v1.2.15

* Stop using symfony roles; use our authz policies instead
* Modernize and refactor

## v1.2.14

* Add support for kevinrob/guzzle-cache-middleware v6
* Drop support for Symfony 5
* Drop support for api-platform 2
* Test with PHP 8.4
* Port to phpstan v2

## v1.2.13

* Adapt to updated DummyPersonProvider

## v1.2.12

* Port to PHPUnit 10
* Port from doctrine annotations to PHP attributes

## v1.2.8

* Add support for api-platform 3.2
* Fix tests with newer base-person bundle

## v1.2.7

* Add support for Symfony 6

## v1.2.6

* Drop support for PHP 7.4/8.0

## v1.2.5

* Drop support for PHP 7.3
* Make sure to use `application/ld+json` for API docs examples

## v1.2.2

* Support kevinrob/guzzle-cache-middleware v5

## v1.2.1

* Add back dummy GET endpoints for all resources, which were removed with the api-platform transition since api-platform still requires them to be present, even if they are not functional. No functional change for usable endpoints.

## v1.2.0

* Port to the new api-platform metadata system. This removes some hidden GET endpoints that were not functional anyway.

## v1.1.7

* Use the global "cache.app" adapter for caching instead of always using the filesystem adapter

## v1.1.6

* Update to api-platform 2.7

## v1.1.3

* tests: don't fail if Dotenv is installed