# Changelog

All notable changes to this project will be documented in this file.

The format is based on **Keep a Changelog**, and this project adheres to **Semantic Versioning**.

## [Unreleased]

### Added
- `BsDate::format()` for Carbon-like formatting of BS dates.
- `BsDate::monthName()` and `BsDate::weekdayName()` helpers.
- Carbon macros:
  - `toBs()` (AD -> BS)
  - `formatBs()` (AD -> BS -> formatted string)
  - `formatAdLocalized()` (locale-aware AD month/weekday + optional Nepali digits)
- Flexible input normalization for conversion methods:
  - **BS → AD** accepts ints, `BsDate`, arrays, and `"YYYY-MM-DD"`-style strings.
  - **AD → BS** accepts Carbon/DateTimeInterface, arrays, `(Y,m,d)` ints, and any Carbon-parseable string.
- README expanded with full input examples for both directions (Packagist install flow).

### Changed
- Composer constraints updated to support Laravel **9–12**:
  - `illuminate/support`: `^9.0|^10.0|^11.0|^12.0`
  - `illuminate/console`: `^9.0|^10.0|^11.0|^12.0`
- Carbon compatibility updated for Laravel 12:
  - `nesbot/carbon`: `^2.0|^3.0`

### Fixed
- Clarified Blade usage: date strings must be quoted (e.g., `'2082-11-14'`), otherwise PHP evaluates it as math.

---

## [0.2.0] - 2026-02-26

### Added
- Flexible `bsToAd()` / `adToBs()` inputs (string/array/object/int) while keeping original signature compatibility.
- Carbon macros and `BsDate` formatting helpers.

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
