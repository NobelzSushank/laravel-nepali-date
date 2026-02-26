# Changelog

All notable changes to this project will be documented in this file.

The format is based on **Keep a Changelog**, and this project adheres to **Semantic Versioning**.

## [Unreleased]

### Added
- Flexible input normalization for conversion methods:
  - **BS → AD** accepts ints, `BsDate`, arrays, and `"YYYY-MM-DD"`-style strings.
  - **AD → BS** accepts Carbon/DateTimeInterface, arrays, `(Y,m,d)` ints, and any Carbon-parseable string.
- Expanded README examples for all supported input forms and outputs.

### Changed
- Composer constraints updated to support Laravel **9–12**:
  - `illuminate/support`: `^9.0|^10.0|^11.0|^12.0`
  - `illuminate/console`: `^9.0|^10.0|^11.0|^12.0`
- Carbon compatibility updated for Laravel 12:
  - `nesbot/carbon`: `^2.0|^3.0`

### Fixed
- Documentation corrected to use the actual Composer package name: `nobelzsushank/laravel-nepali-date`.
- Clarified that the **active** dataset file is the published one under `storage/app/bsad/bsad.json`.

---

## [0.2.0] - 2026-02-26

### Added
- `bsToAd()` and `adToBs()` now accept multiple input formats (string/array/object/int) while remaining compatible with the original `(y,m,d)` signature.

---

## [0.1.0] - 2026-02-26

### Added
- Data-driven BS ↔ AD converter core (anchor-based, dataset-backed).
- User-editable dataset published to `storage/app/bsad/bsad.json`.
- Optional dataset updater command: `php artisan bs:update-data` (downloads JSON, validates schema, optional backup).
- Formatting layer for BS and AD:
  - Locale-aware month names (English/Nepali)
  - Locale-aware weekday names
  - Optional Nepali digits output
  - Extensible token-based formatting (`Y,y,m,n,d,j,F,l`).
- Facade + service provider auto-discovery for Laravel.
- Orchestra Testbench coverage with a fixture dataset.

