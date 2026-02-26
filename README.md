# Laravel Nepali Date (BS ↔ AD)

A **data-driven** Bikram Sambat (BS) ↔ Gregorian (AD) date converter for Laravel.

- ✅ **BS → AD** and **AD → BS**
- ✅ **Flexible inputs** (string/array/Carbon/BsDate/ints)
- ✅ **User-editable dataset** (JSON) stored in your app’s `storage/`
- ✅ Optional dataset updater: `php artisan bs:update-data`
- ✅ Formatter helpers: month names (EN/NP), weekdays (EN/NP), Nepali digits
- ✅ Carbon macros + `BsDate` helpers for ergonomic usage

> License: **MIT**  
> Note: The **code** is MIT. Ensure any **calendar dataset** you ship/download allows redistribution.

---

## Requirements

- PHP **8.1+**
- Laravel **9 / 10 / 11 / 12**
- `nesbot/carbon` **^2 | ^3**

---

## Installation

```bash
composer require nobelzsushank/laravel-nepali-date
```

Publish config + dataset:

```bash
php artisan vendor:publish --tag=bsad-config
php artisan vendor:publish --tag=bsad-data
```

This creates:

- `config/bsad.php`
- `storage/app/bsad/bsad.json` ✅ (**active** dataset file)

---

## Configuration

`config/bsad.php` options:

- `data_path` – where the app reads the dataset  
  Default: `storage/app/bsad/bsad.json`
- `update_url` – dataset URL used by `bs:update-data` (optional)
- `backup_on_update` – keep backups when updating (default: true)
- `locale` – default locale: `en` or `np`
- `nepali_digits` – output digits in Nepali (true/false)

Example `.env` (optional updater):

```env
BSAD_UPDATE_URL=https://example.com/bsad.json
```

---

## Quick Usage

### AD → BS (including “today”)

```php
use NobelzSushank\Bsad\Facades\Bsad;

$bsToday = Bsad::adToBs(now('Asia/Kathmandu'));

echo (string) $bsToday;  // "2082-11-12" (example)
echo $bsToday->year;     // year only
echo $bsToday->month;    // month number (1-12)
echo $bsToday->day;      // day number
```

### BS → AD

```php
use NobelzSushank\Bsad\Facades\Bsad;

$ad = Bsad::bsToAd(2082, 11, 14);          // CarbonImmutable
echo $ad->toDateString();

echo Bsad::bsToAdDateString(2082, 11, 14); // "YYYY-MM-DD"
```

> Blade example (remember: strings must be quoted)
```blade
@php
  $ad = Bsad::bsToAd('2082-11-14');
@endphp

{{ $ad->toDateString() }}
{{ Bsad::bsToAdDateString([2082, 11, 14]) }}
```

---

## Flexible Inputs

### `bsToAd(...)` accepted inputs (BS → AD)

All of the following are supported:

#### 1) Integers (classic)
```php
Bsad::bsToAd(2082, 11, 14);
Bsad::bsToAdDateString(2082, 11, 14);
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

## Ergonomic APIs

### `BsDate` helpers
`adToBs()` returns a `BsDate` object with handy methods:

```php
$bs = Bsad::adToBs(now('Asia/Kathmandu'));

echo $bs->format('Y F d, l', 'np', true);  // formatted BS string
echo $bs->monthName('np');                 // BS month name
echo $bs->weekdayName('en');               // weekday name
echo $bs->toAd()->toDateString();          // convert back to AD
```

### Carbon macros
The package registers Carbon macros so you can do:

```php
$ad = now('Asia/Kathmandu');

echo $ad->formatBs('Y F d, l', 'np', true);          // AD -> BS -> formatted
$bs = $ad->toBs();                                   // -> BsDate
echo $ad->formatAdLocalized('Y F d, l', 'np', true);  // localized AD month/weekday + optional Nepali digits
```

> Carbon’s built-in `format()` only accepts one argument, so we provide `formatBs()` and `formatAdLocalized()` macros.

---

## Updating the Dataset

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

---

## Dataset Format (common error)

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

If you see:

> `BSAD dataset meta.bs_anchor invalid`

Reset:

```bash
rm -f storage/app/bsad/bsad.json
php artisan vendor:publish --tag=bsad-data --force
```

---

## Supported Range

This package is **data-driven**, so supported years depend on your dataset.

If you try to convert a BS year not present in `years`, you’ll get:

> Unsupported BS year XXXX. Dataset supports YYYY–ZZZZ.

---

## License

MIT — see `LICENSE`.

---

## Changelog

See `CHANGELOG.md`.
