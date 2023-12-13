# Telescope Guzzle Watcher

[![Latest Version on Packagist](https://img.shields.io/packagist/v/muhammadhuzaifa/telescope-guzzle-watcher.svg?style=flat-square)](https://packagist.org/packages/muhammadhuzaifa/telescope-guzzle-watcher)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/huzaifaarain/telescope-guzzle-watcher/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/huzaifaarain/telescope-guzzle-watcher/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/muhammadhuzaifa/telescope-guzzle-watcher.svg?style=flat-square)](https://packagist.org/packages/muhammadhuzaifa/telescope-guzzle-watcher)

---

Telescope Guzzle Watcher provide a custom watcher for intercepting http requests made via [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) php library. The package uses the [on_stats](https://docs.guzzlephp.org/en/stable/request-options.html#on-stats) request option for extracting the request/response data. The watcher intercept and log the request into the [Laravel Telescope](https://laravel.com/docs/telescope) [HTTP Client Watcher](https://laravel.com/docs/telescope#http-client-watcher).

Once the installation and configurations are completed, you will be able to see the request logs under `telescope/client-requests`

## Installation

You can install the package via composer:

```bash
composer require muhammadhuzaifa/telescope-guzzle-watcher
```

## Usage

You can publish the config file with:

```bash
php artisan vendor:publish --tag="telescope-guzzle-watcher-config"
```

This is the contents of the published config file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Except Request Headers
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to exclude the request headers from
    | being recorded under the telescope. You can exclude any number of
    | headers containing sensitive information
    |
    */

    'except_request_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Except Response Headers
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to exclude the response headers from
    | being recorded under the telescope. You can exclude any number of
    | headers containing sensitive information
    |
    */

    'except_response_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Enable URI Tags
    |--------------------------------------------------------------------------
    |
    | This value is used for determining wether the watcher should parse the url
    | and add it's segments as telescope tags
    |
    |
    */

    'enable_uri_tags' => true,

    /*
    |--------------------------------------------------------------------------
    | Exclude words from URI tags
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to exclude words or patterns that should
    | be excluded from the tags list
    |
    */

    'exclude_words_from_uri_tags' => [],

    /*
    |--------------------------------------------------------------------------
    | Content Size Limit
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to limit the response content.
    | Default is 64.
    |
    */

    'size_limit' => null,
];
```

You can set the headers that needs to be excluded such as API Keys or other sensitive information. You can also tag uri segments by converting them into an array. This feature can be toggled true/false.

Edit `config/telescope.php` file and add the watcher

```php
return [
    // other telescope configurations
     \MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers\TelescopeGuzzleWatcher::class,
];
```

The watcher depends on the `Service Container` and every instance of guzzle client must be resolve using `Service Container`.

```php
$client = app(\GuzzleHttp\Client::class); // will work
$client = new \GuzzleHttp\Client(); // will not work
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Muhammad Huzaifa](https://muhammadhuzaifa.pro)

## Other Projects

- [Laravel Pulse Guzzle Recorder](https://packagist.org/packages/muhammadhuzaifa/laravel-pulse-guzzle-recorder)
    - Laravel Pulse Guzzle Recorder provide a custom recorder for intercepting http requests made via guzzlehttp/guzzle php library and log them into the Laravel Pulse Slow Outgoing Requests section.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
