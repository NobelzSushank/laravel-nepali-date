# Changelog

All notable changes to this project will be documented in this file.

The format is based on **Keep a Changelog**, and this project adheres to **Semantic Versioning**.

## [Unreleased]

### Added
- Data-driven BS ↔ AD converter core (anchor-based, dataset-backed).
- User-editable dataset support via published file in `storage/app/bsad/bsad.json`.
- Optional dataset updater command: `php artisan bs:update-data` (downloads JSON, validates schema, optional backup).
- Formatting layer for BS and AD:
  - Locale-aware month names (English/Nepali)
  - Locale-aware weekday names
  - Optional Nepali digits output
  - Extensible token-based formatting (`Y,y,m,n,d,j,F,l`).
- Facade + service provider auto-discovery for Laravel.
- Basic Orchestra Testbench coverage with fixture dataset.

### Changed
- Package namespace/vendor aligned to `NobelzSushank` (PSR-4: `NobelzSushank\Bsad\`).

### Fixed
- Composer compatibility for modern Laravel projects by expanding framework component constraints:
  - `illuminate/support`: `^9.0|^10.0|^11.0|^12.0`
  - `illuminate/console`: `^9.0|^10.0|^11.0|^12.0`
  - `nesbot/carbon`: `^2.0|^3.0` (Laravel 12 uses Carbon 3)

---

## [0.1.0] - 2026-02-26

### Added
- Initial release scaffolding:
  - Core BS ↔ AD conversion (dataset-backed)
  - Facade + service provider
  - Config + published dataset file
  - Update command (`bs:update-data`)
  - Formatter utilities (EN/NP, Nepali digits)
  - Tests (Testbench)
