# Laravel Nepali Date (BS ↔ AD) — NobelzSushank

A **data-driven** Bikram Sambat (BS) ↔ Gregorian (AD) date converter for Laravel.

- ✅ **BS → AD** and **AD → BS**
- ✅ **User-editable dataset** (JSON) stored in your app’s `storage/`
- ✅ Optional **Artisan updater**: `php artisan bs:update-data` (pull latest dataset from a URL)
- ✅ Formatting helpers: month names (English/Nepali), weekdays, Nepali digits
- ✅ Flexible inputs for conversion methods (string/array/Carbon/etc.)
- ✅ Designed to be **easy to extend** (add more tokens/locales later)

> License: **MIT**  
> Note: The **code** is MIT. The **calendar dataset** you ship/download should have a license/attribution that allows redistribution.

---

## Requirements

- PHP **8.1+**
- Laravel **9 / 10 / 11 / 12**
- `nesbot/carbon` **^2 | ^3**

---

## Composer Package Name

This repository’s Composer package name is:

```
nobelzsushank/laravel-nepali-date
```

So you install it using that name (not `nobelzsushank/bsad`) unless you change the `"name"` field in this package’s `composer.json`.

---

## Installation

### A) If published on Packagist (later)
```bash
composer require nobelzsushank/laravel-nepali-date
```

### B) Install directly from GitHub (recommended while testing)

1) Add a VCS repository entry in your Laravel app’s `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/NobelzSushank/laravel-nepali-date"
    }
  ]
}
```

2) Require the dev branch:

```bash
./vendor/bin/sail composer require nobelzsushank/laravel-nepali-date:dev-main
```

If your default branch is `master`, use `:dev-master`.

#### Private repo note
If the repo is private, Composer will ask for a GitHub token. Use a fine-grained token with **Contents: Read** for the specific repo.

---

### C) Local development (path repository)

If your package lives on disk next to your Laravel app:

```json
{
  "repositories": [
    { "type": "path", "url": "../laravel-nepali-date", "options": { "symlink": true } }
  ]
}
```

Then:

```bash
./vendor/bin/sail composer require nobelzsushank/laravel-nepali-date:"*@dev"
```

---

## Publish config + dataset

```bash
php artisan vendor:publish --tag=bsad-config
php artisan vendor:publish --tag=bsad-data
```

This creates:

- `config/bsad.php`
- `storage/app/bsad/bsad.json` ✅ (**active** dataset file)

---

## Configuration

`config/bsad.php`:

- `data_path` – where the app reads the dataset  
  Default: `storage/app/bsad/bsad.json`
- `update_url` – dataset URL used by `bs:update-data` (optional)
- `backup_on_update` – keep backups when updating (default: true)
- `locale` – default locale: `en` or `np`
- `nepali_digits` – output digits in Nepali (true/false)

Example `.env` (optional updater):

```env
BSAD_UPDATE_URL=https://raw.githubusercontent.com/<YOUR_ORG>/<YOUR_DATA_REPO>/main/bsad.json
```

---

## Quick Usage

### AD → BS (including “today”)
```php
use NobelzSushank\Bsad\Facades\Bsad;

// Today in Nepal time:
$bsToday = Bsad::adToBs(now('Asia/Kathmandu'));

echo (string) $bsToday;  // "2082-11-12" (example)
echo $bsToday->year;     // year only
echo $bsToday->month;    // month number (1-12)
echo $bsToday->day;      // day number
```

### BS → AD
```php
use NobelzSushank\Bsad\Facades\Bsad;

$ad = Bsad::bsToAd(2082, 11, 14);  // CarbonImmutable
echo $ad->toDateString();

echo Bsad::bsToAdDateString(2082, 11, 14); // "YYYY-MM-DD"
```

> Blade example:
```blade
@php
  $ad = Bsad::bsToAd('2082-11-14');
@endphp

{{ $ad->toDateString() }}
{{ Bsad::bsToAdDateString([2082, 11, 14]) }}
```

---

## Flexible Inputs (BS → AD and AD → BS)

### `bsToAd(...)` accepted inputs (BS → AD)

All of the following are supported:

#### 1) Integers (classic)
```php
Bsad::bsToAd(2082, 11, 14);        // -> CarbonImmutable
Bsad::bsToAdDateString(2082, 11, 14); // -> "YYYY-MM-DD"
```

#### 2) String “YYYY-MM-DD” (also / . space separators)
```php
Bsad::bsToAd('2082-11-14')->toDateString();
Bsad::bsToAd('2082/11/14')->toDateString();
Bsad::bsToAd('2082.11.14')->toDateString();
Bsad::bsToAd('2082 11 14')->toDateString();
```

#### 3) List array
```php
Bsad::bsToAd([2082, 11, 14])->toDateString();
```

#### 4) Associative array (either key style)
```php
Bsad::bsToAd(['y' => 2082, 'm' => 11, 'd' => 14])->toDateString();
Bsad::bsToAd(['year' => 2082, 'month' => 11, 'day' => 14])->toDateString();
```

#### 5) `BsDate` object
```php
use NobelzSushank\Bsad\ValueObjects\BsDate;

Bsad::bsToAd(new BsDate(2082, 11, 14))->toDateString();
```

> Output: `bsToAd()` returns a **CarbonImmutable** in timezone `Asia/Kathmandu` (dataset `meta.tz`).

---

### `adToBs(...)` accepted inputs (AD → BS)

All of the following are supported:

#### 1) Any Carbon-parseable string
```php
Bsad::adToBs('2026-02-26');              // -> BsDate
Bsad::adToBs('2026-02-26 10:30:00');     // time ignored (uses startOfDay)
Bsad::adToBs('next monday');             // Carbon parsing rules apply
Bsad::adToBsString('2026-02-26');        // -> "YYYY-MM-DD" (BS)
```

#### 2) Carbon / DateTimeInterface
```php
Bsad::adToBs(now('Asia/Kathmandu'));
Bsad::adToBs(now()->toImmutable());
Bsad::adToBs(new DateTime('2026-02-26'));
```

#### 3) Integers or arrays
```php
Bsad::adToBs(2026, 2, 26);
Bsad::adToBs([2026, 2, 26]);
Bsad::adToBs(['y' => 2026, 'm' => 2, 'd' => 26]);
Bsad::adToBs(['year' => 2026, 'month' => 2, 'day' => 26]);
```

> Output: `adToBs()` returns a **BsDate** value object (`year`, `month`, `day`).

---

## Month/Year/Day helpers

Because `adToBs()` returns a `BsDate` object:

```php
$bs = Bsad::adToBs(now('Asia/Kathmandu'));

$year  = $bs->year;
$month = $bs->month;
$day   = $bs->day;
```

For AD month/year/day, use Carbon:

```php
$ad = Bsad::bsToAd('2082-11-14');

$year  = (int) $ad->format('Y');
$month = (int) $ad->format('n');
$day   = (int) $ad->format('j');
```

---

## Formatting (month names, weekdays, Nepali digits)

Formatting is provided by the `Formatter` service:

```php
$conv = app(\NobelzSushank\Bsad\Converters\BsadConverter::class);
$fmt  = app(\NobelzSushank\Bsad\Formatting\Formatter::class);

$bs = $conv->adToBs('2026-02-24');

// English month name
echo $fmt->formatBs($bs, 'Y F d, l', 'en', false);

// Nepali month name + Nepali digits
echo $fmt->formatBs($bs, 'Y F d, l', 'np', true);
```

### Supported format tokens (BS)
| Token | Meaning |
|------:|---------|
| `Y` | 4-digit BS year |
| `y` | 2-digit BS year |
| `m` | 2-digit BS month (`01`–`12`) |
| `n` | BS month number (`1`–`12`) |
| `d` | 2-digit BS day |
| `j` | BS day number |
| `F` | BS month name (locale-aware) |
| `l` | weekday name (computed via converted AD date, locale-aware) |

### Supported format tokens (AD)
`Formatter::formatAd()` supports the same tokens, but based on the Gregorian date.

---

## Updating the Dataset (No Manual Intervention)

If you host a newer `bsad.json` (GitHub raw URL, S3, etc.), you can update the local dataset:

```bash
php artisan bs:update-data
```

Or override URL/path:

```bash
php artisan bs:update-data --url="https://example.com/bsad.json"
php artisan bs:update-data --path="/full/path/to/bsad.json"
```

### Octane / queue workers
If your app is long-running, reload after updating:
- `php artisan octane:reload`
- `php artisan queue:restart`

---

## Let Users Edit the Data Themselves

The active dataset is stored in:

- `storage/app/bsad/bsad.json`

Users can edit that file directly to patch a year/month if needed.

You can also point to a different file via `config/bsad.php`:

```php
'data_path' => storage_path('app/bsad/custom-bsad.json'),
```

---

## Dataset Format (and common error)

Your `bsad.json` must contain `meta.bs_anchor` as an object:

```json
{
  "meta": {
    "tz": "Asia/Kathmandu",
    "ad_anchor": "1943-04-14",
    "bs_anchor": { "y": 2000, "m": 1, "d": 1 },
    "source": "NPNS",
    "version": "2026.02"
  },
  "years": {
    "2000": [30,32,31,32,31,30,30,30,29,30,29,31]
  }
}
```

### If you see: `BSAD dataset meta.bs_anchor invalid`
It means the JSON does **not** match the expected schema (often `bs_anchor` is missing, a string, or uses different keys).

Quick reset:

```bash
rm -f storage/app/bsad/bsad.json
php artisan vendor:publish --tag=bsad-data --force
```

Then open `storage/app/bsad/bsad.json` and verify the `meta.bs_anchor` block.

---

## Supported Range

This package is **data-driven**, so supported years depend on your dataset.

If you try to convert a year not present in `years`, you’ll get:

> Unsupported BS year XXXX. Dataset supports YYYY–ZZZZ.

To extend support, update the dataset (new release / `bs:update-data` / user edit).

---

## Testing (package repo)

```bash
composer test
```

(Assuming you add a script, e.g. `"test": "vendor/bin/phpunit"`.)

---

## License

MIT — see `LICENSE`.

---

## Changelog

See `CHANGELOG.md`.
