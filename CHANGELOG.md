# Changelog

All notable changes to `telescope-guzzle-watcher` will be documented in this file.

## v4.0.0 - 2025-09-26

### 4.x – Telescope Guzzle Watcher

#### Breaking Changes

- Bumped minimum requirements to **PHP 8.4** and **Laravel 12.x**. Projects on older versions need to upgrade before installing this release.
- Development tooling now targets **Orchestra Testbench 10.x**. Update your package dev dependencies if you run the Testbench suite locally.
- The watcher expects Guzzle clients to be resolved through Laravel’s service container. Any direct `new Client()` instantiations must be replaced with `app(GuzzleHttp\Client::class, [...])` to keep Telescope logging intact.

#### Added

- A full developer guide in `README.md`, including installation, configuration deep dives, multi-client patterns, and troubleshooting tips.
- A hands-on integration example showing how to route the official HubSpot PHP SDK through the Telescope-aware Guzzle client.
- A strict PHPStan profile and an expanded PHPUnit suite that achieve 100 % code and type coverage.

#### Changed

- Multipart payload handling is cleaner: a dedicated parser processes multipart sections, non-multipart requests defer to the parent implementation, and `array_find_key` simplifies header detection.
- The custom Guzzle binding now validates incoming config arrays, preserves existing `on_stats` callbacks, and normalises URI tag generation and filtering.
- Coding standards across watcher and test files were refreshed for consistency and easier contributions.

#### Fixed

- Edge cases in tag filtering, duration capture, and payload extraction were resolved, ensuring Telescope entries remain accurate even with unusual responses.
- #20 Correct Guzzle client binding in Service Container

#### CI

- GitHub Actions now runs against **PHP 8.4**, **Laravel 12**, and **Testbench 10**, aligning automated checks with the package’s supported stack.

## v3.2.2 - 2025-03-06

### What's Changed

* Laravel 12.x Compatibility by @laravel-shift in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/16

### New Contributors

* @laravel-shift made their first contribution in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/16

**Full Changelog**: https://github.com/huzaifaarain/telescope-guzzle-watcher/compare/v3.2.1...v3.2.2

## v3.2.1 - 2024-11-15

**Full Changelog**: https://github.com/huzaifaarain/telescope-guzzle-watcher/compare/v3.2.0...v3.2.1

## v3.2.0 - 2024-10-26

### What's Changed

* feat: add support for multipart data by @huzaifaarain in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/14

#### Added Multipart Support

The package now support multipart requests from Guzzle, however for the sake of simplicity each content item with the Content-Type specified will be logged without rendering the file in raw or parsed format.

### New Contributors

* @huzaifaarain made their first contribution in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/14

**Full Changelog**: https://github.com/huzaifaarain/telescope-guzzle-watcher/compare/v3.1.0...v3.2.0

## v3.1.0 - 2024-07-02

### What's Changed

* Handle unreachable hosts by @curiousyigit in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/10

### New Contributors

* @curiousyigit made their first contribution in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/10

**Full Changelog**: https://github.com/huzaifaarain/telescope-guzzle-watcher/compare/v3.0.0...v3.1.0

## v3.0.0 - 2024-03-17

### What's Changed

* Add Support For Laravel 11 by @ziarv in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/6

### New Contributors

* @ziarv made their first contribution in https://github.com/huzaifaarain/telescope-guzzle-watcher/pull/6

**Full Changelog**: https://github.com/huzaifaarain/telescope-guzzle-watcher/compare/v2.0.1...v3.0.0

## v2.0.1 - 2023-06-06

- Update test workflow
- Added unit test for the watcher
- Update composer deps

## v2.0.0 - 2023-05-29

### Added

- Added `MuhammadHuzaifa\TelescopeGuzzleWatcher\Request`
- Added `MuhammadHuzaifa\TelescopeGuzzleWatcher\Response`
- Added `MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleRecorder`

### Refactoring

`MuhammadHuzaifa\TelescopeGuzzleWatcher\Watchers\TelescopeGuzzleWatcher` has been completely refactored and rewritten. To follow the Design Principles, the core functionality has been extracted to `MuhammadHuzaifa\TelescopeGuzzleWatcher\TelescopeGuzzleRecorder`.

## v1.2.0 - 2023-05-03

- feat: add support for existing on_stats closure via config
- feat: remove ISSUE_TEMPLATE
- feat: add fix php code style workflow

## 1.1.0 - 2023-04-28

- feat: add docs for uri to tags feature and fix styling
- feat: add shield badges
- feat: add feature for converting request uri to tags
- feat: add auth user for the IncomingEntry
- refactor: extract method makeEntry
- refactor: follow laravel default style for comments
- feat: add comments for config keys
