# Telescope Guzzle Watcher

[![Latest Version on Packagist](https://img.shields.io/packagist/v/muhammadhuzaifa/telescope-guzzle-watcher.svg?style=flat-square)](https://packagist.org/packages/muhammadhuzaifa/telescope-guzzle-watcher)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/huzaifaarain/telescope-guzzle-watcher/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/huzaifaarain/telescope-guzzle-watcher/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/muhammadhuzaifa/telescope-guzzle-watcher.svg?style=flat-square)](https://packagist.org/packages/muhammadhuzaifa/telescope-guzzle-watcher)

---

This is a simple package that provide custom watcher for intercepting http requests using `guzzlehttp/guzzle` library. The `TelescopeGuzzleWatcher` uses the [on_stats](https://docs.guzzlephp.org/en/stable/request-options.html#on-stats) request option. The watcher extract the data by rewinding the request and response and then it uses the `Telescope::recordClientRequest` for recording the requests in a telescope.

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
    "except_request_headers" => [],
    "except_response_headers" => [],
    "enable_uri_tags" => true,
    "exclude_words_from_uri_tags" => [],
];
```

You can set the headers that needs to be excluded such API Keys or other sensitive information. You can also tag uri segments by converting them into an array. This feature can be toggled true/false.

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

- [Muhammad Huzaifa](https://github.com/huzaifaarain)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
