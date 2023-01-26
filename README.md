# Very short description of the package

<!--
[![Latest Version on Packagist](https://img.shields.io/packagist/v/alancolant/laravel-pgsync.svg?style=flat-square)](https://packagist.org/packages/alancolant/laravel-pgsync)
[![Total Downloads](https://img.shields.io/packagist/dt/alancolant/laravel-pgsync.svg?style=flat-square)](https://packagist.org/packages/alancolant/laravel-pgsync)
-->
![GitHub Actions](https://github.com/alancolant/laravel-pgsync/actions/workflows/run-tests.yml/badge.svg)

This package allows for real-time syncing of a PostgreSQL database with Elasticsearch using pg_notify mechanisms.

It is designed to work seamlessly with Laravel, and can be easily integrated into any existing Laravel project.

With this package, you can keep your Elasticsearch index in sync with your PostgreSQL database automatically and in real-time, without the need for manual imports or exports.

## Installation

You can install the package via composer:

```bash
composer require alancolant/laravel-pgsync
```

## Usage

To start the sync process, simply run the following command:

```bash
php artisan pgsync:listen
```

This will start listening for pg_notify events and updating Elasticsearch accordingly.

## Configuration

You can configure the package by publishing its configuration file and editing it:

```bash
php artisan vendor:publish --provider="Alancolant\LaravelPgsync\LaravelPgsyncServiceProvider"
```

This will create a `pgsync.php` file in your config directory. You can edit this file to configure the package according to your needs.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email contact@alancolant.com instead of using the issue tracker.

## Credits

- [Alan COLANT](https://github.com/alancolant)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
