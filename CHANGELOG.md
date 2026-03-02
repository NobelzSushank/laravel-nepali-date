# Changelog

All notable changes to this project will be documented in this file.

The format is based on **Keep a Changelog**, and this project adheres to **Semantic Versioning**.

## [Unreleased]

### Added
- Storage-based dataset workflow: runtime reads from `storage/app/bsad/bsad.json` so applications can patch calendar data without editing `vendor/` files.
- Flexible input support for conversion methods:
  - **BS → AD** accepts ints `(y,m,d)`, `BsDate`, list arrays, associative arrays, and `"YYYY-MM-DD"`-style strings (also `/`, `.`, spaces).
  - **AD → BS** accepts `Carbon`/`DateTimeInterface`, arrays, `(Y,m,d)` ints, and any Carbon-parseable string.
- Carbon-like ergonomics:
  - `BsDate::format()` token formatting with locale + Nepali digits options
  - `BsDate::monthName()` and `BsDate::weekdayName()`
  - Carbon macros: `toBs()`, `formatBs()`, `formatAdLocalized()`
- Formatter improvements:
  - Token parser supports escaping with backslash (e.g. `\Y` for literal `Y`)
  - Locale-aware month and weekday names (English/Nepali)
  - Optional Nepali digit rendering

### Changed
- Composer compatibility expanded to support Laravel **9–12**:
  - `illuminate/support`: `^9.0|^10.0|^11.0|^12.0`
  - `illuminate/console`: `^9.0|^10.0|^11.0|^12.0`
- Carbon compatibility expanded for Laravel 12:
  - `nesbot/carbon`: `^2.0|^3.0`
- Documentation updated for Packagist install flow and storage dataset editing guidance.

---

### [0.2.1] - 2026-03-01
- Minor bug fixes and improvement.

---

### [0.2.1] - 2026-03-01
- Correct formatting output for patterns like `Y F d, l` (previously could fall back to `YYYY-MM-DD`).
- Clarified Blade usage: date strings must be quoted (e.g., `'2082-11-14'`) to avoid PHP math evaluation.

---

## [0.2.0] - 2026-02-26

### Added
- Flexible `bsToAd()` / `adToBs()` input normalization (string/array/object/int) while keeping original `(y,m,d)` compatibility.
- Carbon macros and `BsDate` formatting helpers.

### Fixed
- Improved token parsing to reliably substitute `F` (month name) and `l` (weekday name).

---

## [0.1.0] - 2026-02-26

### Added
- Initial release:
  - Data-driven BS ↔ AD conversion using an anchor date + month-length dataset
  - Published config and dataset file
  - Optional dataset updater command
  - Basic formatter utilities (EN/NP, Nepali digits)
  - Orchestra Testbench coverage
