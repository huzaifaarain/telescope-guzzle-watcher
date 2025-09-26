# We Stand With Palestine, Pakistan Stand With Palestine

![StandWithPalestine](https://raw.githubusercontent.com/huzaifaarain/huzaifaarain/master/assets/solidarity-palestine.png)

## Telescope Guzzle Watcher

[![Latest Version on Packagist](https://img.shields.io/packagist/v/muhammadhuzaifa/telescope-guzzle-watcher.svg?style=flat-square)](https://packagist.org/packages/muhammadhuzaifa/telescope-guzzle-watcher)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/huzaifaarain/telescope-guzzle-watcher/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/huzaifaarain/telescope-guzzle-watcher/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/muhammadhuzaifa/telescope-guzzle-watcher.svg?style=flat-square)](https://packagist.org/packages/muhammadhuzaifa/telescope-guzzle-watcher)

---

Telescope Guzzle Watcher provide a custom watcher for intercepting http requests made via [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) php library. The package uses the [on_stats](https://docs.guzzlephp.org/en/stable/request-options.html#on-stats) request option for extracting the request/response data. The watcher intercept and log the request into the [Laravel Telescope](https://laravel.com/docs/telescope) [HTTP Client Watcher](https://laravel.com/docs/telescope#http-client-watcher).

Once the installation and configurations are completed, you will be able to see the request logs under `telescope/client-requests`

## Table of Contents

- [We Stand With Palestine, Pakistan Stand With Palestine](#we-stand-with-palestine-pakistan-stand-with-palestine)
  - [Telescope Guzzle Watcher](#telescope-guzzle-watcher)
  - [Table of Contents](#table-of-contents)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [How It Works](#how-it-works)
  - [Configuration Reference](#configuration-reference)
  - [Registering the Watcher](#registering-the-watcher)
  - [Resolving Guzzle Clients](#resolving-guzzle-clients)
  - [Using Multiple Clients](#using-multiple-clients)
  - [Testing \& Verification](#testing--verification)
  - [Troubleshooting](#troubleshooting)
  - [Changelog](#changelog)
  - [Credits](#credits)
  - [Other Projects](#other-projects)
  - [License](#license)

## Requirements

- PHP ^8.4
- Laravel 12.x
- [Laravel Telescope](https://laravel.com/docs/telescope) ^5.11
- [Guzzle](https://github.com/guzzle/guzzle) ^7.9

## Installation

Install the package via Composer:

```bash
composer require muhammadhuzaifa/telescope-guzzle-watcher
```

Laravel's package auto-discovery will register the service provider for you. No manual provider entry is required unless auto-discovery is disabled.

After the package is installed, publish the configuration file so that you can tweak the watcher defaults:

```bash
php artisan vendor:publish --tag="telescope-guzzle-watcher-config"
```

The published file lives at `config/telescope-guzzle-watcher.php`.

## How It Works

1. **Client Hijacking via the Container** – The package binds `GuzzleHttp\Client` in the service container to a custom [Guzzle Client Factory](src/GuzzleClientFactory.php). Every time a client is resolved from the container, the factory injects an `on_stats` callback that forwards request and response information to Telescope.
2. **Transfer Stats Hook** – Guzzle fires the `on_stats` callback when a request completes (successfully or not). The watcher converts the raw PSR-7 request/response objects into Laravel's `Illuminate\Http\Client\Request` and `Response` wrappers so that Telescope can index the payload.
3. **Telescope Recording** – When Telescope is actively recording, the watcher forwards each HTTP transfer to the `client-requests` panel, applying configuration such as tag extraction, payload truncation, and header redaction.

Because the watcher is a first-class Telescope watcher, it benefits from all Telescope filters, tags, and search capabilities right out of the box.

## Configuration Reference

The configuration file ships with the following options:

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `except_request_headers` | `array<string>` | `[]` | Header names (case-insensitive) that should be hidden from Telescope for outgoing requests. Useful for API keys or authorization tokens. |
| `except_response_headers` | `array<string>` | `[]` | Header names that should be hidden from Telescope responses. |
| `enable_uri_tags` | `bool` | `true` | When `true`, the watcher will explode the request URI into segments and push them as Telescope tags (`host`, followed by each non-empty path segment). |
| `exclude_words_from_uri_tags` | `array<string>` | `[]` | List of words that should be excluded from the tag list after it is generated. Matching is case-insensitive. |
| `size_limit` | `int|null` | `null` | Overrides Telescope's default 64 KB payload truncation limit when set. Provide the value in kilobytes. `null` defers to Telescope's own limit. |

Tweak these settings in `config/telescope-guzzle-watcher.php` and cache your configuration if your deployment workflow requires it (`php artisan config:cache`).

## Registering the Watcher

Tell Telescope to use this watcher by updating your `config/telescope.php` file:

```php
// config/telescope.php

return [
    'watchers' => [
        // ... other watchers ...
        \MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers\TelescopeGuzzleWatcher::class => true,
    ],
];
```

Set the value to `true` to enable it or to a configuration array if you want to supply watcher-specific options in-line (for example, to disable it per-environment using a closure).

## Resolving Guzzle Clients

For the watcher to intercept traffic you must resolve Guzzle clients through Laravel's service container:

```php
// Correct – the factory will wire up on_stats and Telescope logging
$client = app(\GuzzleHttp\Client::class);

// Optional custom configuration
$client = app(\GuzzleHttp\Client::class, [
    'config' => [
        'base_uri' => 'https://api.example.com',
        'timeout' => 5,
    ],
]);

// Avoid instantiating Guzzle manually if you want Telescope coverage
$client = new \GuzzleHttp\Client(); // no Telescope logging
```

If you already provide an `on_stats` callback, the factory will wrap it so both Telescope and your callback execute.

## Using Multiple Clients

The watcher supports any number of clients or middleware stacks as long as they are resolved through the container. A few patterns you may find useful:

- **Per-service bindings:**

    ```php
    $this->app->bind('billing-client', function () {
        return app(\GuzzleHttp\Client::class, [
            'config' => [
                'base_uri' => config('services.billing.url'),
                'headers' => ['Accept' => 'application/json'],
            ],
        ]);
    });
    ```

- **Custom middleware:** resolve the client as normal, then push additional middleware. Telescope will continue to receive the transfer statistics.

## Testing & Verification

1. Run a request through your application that uses the container-resolved Guzzle client.
2. Visit `https://your-app.test/telescope/client-requests` (or the path configured for Telescope) to verify the entry.
3. Adjust configuration options and clear caches as needed.

Automated testing tips:

- The package itself is tested with [Orchestra Testbench](https://github.com/orchestral/testbench). If you write integration tests, boot Telescope or fake it appropriately to avoid polluting production storage.
- You can disable Telescope during certain tests with `Telescope::withoutRecording(fn () => /* perform requests */);` if you only want to test the client behavior.

## Troubleshooting

- **No entries appear:** Confirm that Telescope is enabled (`TELESCOPE_ENABLED=true`) and that you are resolving Guzzle via the container. Telescope only records when `Telescope::isRecording()` returns `true`.
- **Entries missing payloads:** Increase `size_limit` in the package config or ensure the response body is seekable. Streams that cannot be rewound will be labeled as `Stream Response` by Telescope.
- **Headers still visible:** Remember that header names are case-insensitive; ensure you use the exact header key (for example, `Authorization`) in the `except_*` arrays.
- **Existing on_stats logic not firing:** The factory preserves your callback. If you replace the client configuration elsewhere, make sure you merge with the container-provided config instead of instantiating a fresh client.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Huzaifa Saif-ur-Rehman](https://muhammadhuzaifa.pro)

## Other Projects

- [Laravel Pulse Guzzle Recorder](https://packagist.org/packages/muhammadhuzaifa/laravel-pulse-guzzle-recorder)
    - Laravel Pulse Guzzle Recorder provide a custom recorder for intercepting http requests made via guzzlehttp/guzzle php library and log them into the Laravel Pulse Slow Outgoing Requests section.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
