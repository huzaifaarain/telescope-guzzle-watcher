# Changelog

All notable changes to `telescope-guzzle-watcher` will be documented in this file.

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
