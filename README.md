# Google Cloud Tasks Queue Driver for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tradecoverexchange/google-cloud-tasks-laravel.svg?style=flat-square)](https://packagist.org/packages/tradecoverexchange/google-cloud-tasks-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/tradecoverexchange/google-cloud-tasks-laravel/run-tests?label=tests)](https://github.com/tradecoverexchange/google-cloud-tasks-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/tradecoverexchange/google-cloud-tasks-laravel/Check%20&%20fix%20styling?label=code%20style)](https://github.com/tradecoverexchange/google-cloud-tasks-laravel/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/tradecoverexchange/google-cloud-tasks-laravel.svg?style=flat-square)](https://packagist.org/packages/tradecoverexchange/google-cloud-tasks-laravel)

A Laravel Queue driver to interact with [Google Cloud Tasks](https://cloud.google.com/tasks).

## Installation

Requires Laravel 9 and PHP 8.1 as a minimum.

You can install the package via composer:

```bash
composer require tradecoverexchange/google-cloud-tasks-laravel
```

You can publish an overriding queue config file with:
```bash
php artisan vendor:publish --provider="TradeCoverExchange\GoogleCloudTaskLaravel\CloudTaskServiceProvider" --tag cloud-task-config --force
```

Or you can manually add the two following connections to your own `queues.php` config file:

```php
return [
    'connections' => [
        'app_engine_tasks' => [
            'driver' => 'google_app_engine_cloud_tasks',
            'queue' => env('GOOGLE_CLOUD_TASKS_QUEUE', 'default'),
            'project_id' => env('GOOGLE_CLOUD_TASKS_PROJECT_ID', ''),
            'location' => env('GOOGLE_CLOUD_TASKS_LOCATION_ID', ''),
            'options' => [
                'credentials' => 'path/to/your/keyfile',
                'transport' => 'rest',
            ],
        ],

        'http_cloud_tasks' => [
            'driver' => 'google_http_cloud_tasks',
            'queue' => env('GOOGLE_CLOUD_TASKS_QUEUE', 'default'),
            'project_id' => env('GOOGLE_CLOUD_TASKS_PROJECT_ID', ''),
            'location' => env('GOOGLE_CLOUD_TASKS_LOCATION_ID', ''),
            'authentication' => [
                'token_type' => 'oidc',
                'service_account' => env('GOOGLE_CLOUD_TASKS_SERVICE_ACCOUNT', ''),
            ],
            'options' => [
                'credentials' => 'path/to/your/keyfile',
                'transport' => 'rest',
            ],
        ],
    ],
];
```

## Usage

Usage of the package should primarily be done via the [Laravel Queue](https://laravel.com/docs/7.x/queues) system.

## Missing Features

There is no ability to configure the worker options in the same way as a typical queue connection
in Laravel.

For Http Tasks only the OIDC token type has been implemented for protecting the controller
from fraudulent requests. We don't use OAuth ourselves but would be happy to include if
someone makes a PR for it or knows how that mechanism should work compared to OIDC.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## About Us

![Trade Cover Exchange](https://assets.tradecoverexchange.com/github/TradeCoverExchange_RGB_Logo_Outline_Stacked.png)

[Trade Cover Exchange](https://tradecoverexchange.com) is a platform for insuring your trade
with other companies, protecting you from instabilities in the supply chain.

We proudly use the Google Cloud platform for our service and hope to share more of our work with
the developer community in the future.

## Security

If you discover any security related issues, please email peter@tradecoverexchange.com instead of 
using the issue tracker.

## Credits

- [Peter Fox](https://github.com/peterfox)
- [Kees van Bemmel](https://github.com/kees-tce)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
