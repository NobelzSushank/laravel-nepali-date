# Laravel Nepali Date (BS ↔ AD)

A **data‑driven** Bikram Sambat (BS) ↔ Gregorian (AD) converter for Laravel.

This package is built so your application uses a **local dataset file in `storage/`** (not inside `vendor/`).  
That means **your app can patch/update the calendar data manually** at any time — **without updating the package** — and your changes **won’t be overwritten** by `composer update`.

---

## Highlights

- ✅ Convert **BS → AD** and **AD → BS**
- ✅ **Flexible inputs** for both directions (ints, arrays, strings, `Carbon`, `DateTime`, `BsDate`)
- ✅ **Dataset is user‑editable** at `storage/app/bsad/bsad.json`
  - No vendor edits required
  - Composer updates won’t overwrite your dataset
- ✅ **Carbon‑like formatting**
  - `BsDate::format('Y F d, l', 'np', true)`
- ✅ Output in **English or Nepali** (month names + weekday names)
- ✅ Optional **Nepali digits** output
- ✅ Clean API designed to be easy to extend (more tokens/locales later)

---

## Requirements

- PHP **8.1+**
- Laravel **9 / 10 / 11 / 12**
- `nesbot/carbon` **^2 | ^3**

---

## Installation (Packagist)

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
- `storage/app/bsad/bsad.json` ✅ (**ACTIVE dataset file used at runtime**)

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

## How dataset updates work (no vendor edits)

Your app reads BS month‑day data from:

```
storage/app/bsad/bsad.json
```

So if you need to fix/extend the calendar (e.g., the package author hasn’t updated data yet), you can:

- **edit `storage/app/bsad/bsad.json` manually**, or
- point `bsad.data_path` to another file you manage.

✅ These changes stay in your app — **Composer updates do not touch your `storage/` files**.

### Point to a custom dataset file (optional)

In `config/bsad.php`:

```php
'data_path' => storage_path('app/bsad/custom-bsad.json'),
```

---

## Quick Start

### AD → BS (including today)

```php
use NobelzSushank\Bsad\Facades\Bsad;

$bsToday = Bsad::adToBs(now('Asia/Kathmandu'));

echo (string) $bsToday;   // "2082-11-12" (example)
echo $bsToday->year;      // 2082
echo $bsToday->month;     // 11
echo $bsToday->day;       // 12
```

### BS → AD

```php
use NobelzSushank\Bsad\Facades\Bsad;

$ad = Bsad::bsToAd(2082, 11, 14);     // CarbonImmutable
echo $ad->toDateString();             // 2026-02-26

echo Bsad::bsToAdDateString(2082, 11, 14); // 2026-02-26
```

> ⚠️ Blade tip: BS date strings must be quoted  
> `Bsad::bsToAd(2082-11-14)` is math in PHP. Use `'2082-11-14'`.

---

## Flexible Inputs (All Supported)

### BS → AD: `bsToAd(...)`

All of these are supported:

#### 1) Integers (classic)

```php
Bsad::bsToAd(2082, 11, 14);
Bsad::bsToAdDateString(2082, 11, 14);
```

#### 2) String “YYYY-MM-DD” (also accepts `/`, `.`, spaces)

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

#### 4) Associative array

```php
Bsad::bsToAd(['y' => 2082, 'm' => 11, 'd' => 14])->toDateString();
Bsad::bsToAd(['year' => 2082, 'month' => 11, 'day' => 14])->toDateString();
```

#### 5) `BsDate` object

```php
use NobelzSushank\Bsad\ValueObjects\BsDate;

Bsad::bsToAd(new BsDate(2082, 11, 14))->toDateString();
```

✅ `bsToAd()` returns a **CarbonImmutable** in timezone `Asia/Kathmandu` (from dataset meta).

---

### AD → BS: `adToBs(...)`

All of these are supported:

#### 1) Any Carbon-parseable string

```php
Bsad::adToBs('2026-02-26');                  // -> BsDate
Bsad::adToBs('2026-02-26 10:30:00');         // time ignored (startOfDay)
Bsad::adToBs('next monday');                 // Carbon parsing rules apply
Bsad::adToBsString('2026-02-26');            // -> "YYYY-MM-DD" (BS)
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

✅ `adToBs()` returns a **BsDate** value object (`year`, `month`, `day`).

---

## Carbon-like Formatting (Nepali + English)

### 1) Format BS date: `BsDate::format(...)`

`adToBs()` returns a `BsDate` which supports:

```php
$bs = Bsad::adToBs(now('Asia/Kathmandu'));

// Nepali month + weekday + Nepali digits
echo $bs->format('Y F d, l', 'np', true);

// English month + weekday
echo $bs->format('Y F d, l', 'en', false);

// Default format
echo $bs->format(); // "YYYY-MM-DD"
```

### 2) BS helpers: month/weekday names

```php
$bs = Bsad::adToBs(now('Asia/Kathmandu'));

echo $bs->monthName('np');     // e.g. "फाल्गुण"
echo $bs->monthName('en');     // e.g. "Falgun"

echo $bs->weekdayName('np');   // e.g. "बिहीबार"
echo $bs->weekdayName('en');   // e.g. "Thursday"
```

### 3) Convert BS back to AD easily

```php
$ad = $bs->toAd();
echo $ad->toDateString();
```

---

## Carbon Macros (AD side)

Carbon’s built-in `format()` only accepts **one** argument, so the package adds macros:

```php
$ad = now('Asia/Kathmandu');

$bs = $ad->toBs(); // -> BsDate

echo $ad->formatBs('Y F d, l', 'np', true);           // AD -> BS -> formatted
echo $ad->formatAdLocalized('Y F d, l', 'np', true);  // AD formatting w/ NP month+weekday + Nepali digits
```

---

## Supported Format Tokens

The formatter supports these tokens (BS side and AD-localized side):

| Token | Meaning |
|------:|---------|
| `Y` | 4-digit year |
| `y` | 2-digit year |
| `m` | 2-digit month (`01`–`12`) |
| `n` | month number (`1`–`12`) |
| `d` | 2-digit day |
| `j` | day number |
| `F` | month name (locale-aware) |
| `l` | weekday name (locale-aware) |

> Escaping is supported: `\Y` prints literal `Y`.

---

## Dataset Format (JSON)

Your `storage/app/bsad/bsad.json` must contain:

```json
{
  "meta": {
    "tz": "Asia/Kathmandu",
    "ad_anchor": "1943-04-14",
    "bs_anchor": { "y": 2000, "m": 1, "d": 1 }
  },
  "years": {
    "2000": [30,32,31,32,31,30,30,30,29,30,29,31]
  }
}
```

If you see:

> `BSAD dataset meta.bs_anchor invalid`

it means the JSON does not match the expected schema (missing `bs_anchor`, wrong keys, etc.).

---

## Supported Range

This package is **data-driven**, so supported years depend on your dataset.

If you try to convert a BS year not present in `years`, you’ll get:

> Unsupported BS year XXXX. Dataset supports YYYY–ZZZZ.

To extend support, update the dataset JSON.

---

## License

MIT — see `LICENSE`.

---

## Changelog

See `CHANGELOG.md`.
