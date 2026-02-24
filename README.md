# NobelzSushank BSAD (Bikram Sambat ↔ Gregorian) for Laravel

A **data-driven** Bikram Sambat (BS) ↔ Gregorian (AD) date converter for Laravel.

- **BS → AD** and **AD → BS**
- **User-editable dataset** (JSON) stored in your app’s `storage/`
- Optional **Artisan updater**: `php artisan bs:update-data` (pull latest dataset from a URL)
- Formatting helpers: month names (English/Nepali), weekdays, Nepali digits
- Designed to be **easy to extend** (more format tokens, locales, etc.)

> License: **MIT**  
> Note: The **code** is MIT. The **calendar dataset** you ship/download should have a license/attribution that allows redistribution.

---

## Requirements

- PHP **8.1+**
- Laravel **10+** or **11+**
- `nesbot/carbon`

---

## Installation

Install via Composer:

```bash
composer require nobelzsushank/bsad
```

Publish config + dataset to your Laravel app:

```bash
php artisan vendor:publish --tag=bsad-config
php artisan vendor:publish --tag=bsad-data
```

This will create:

- `config/bsad.php`
- `storage/app/bsad/bsad.json`  ✅ (this is the file you can edit/update)

---

## Configuration

`config/bsad.php`:

- `data_path` – where the app reads the dataset (default: `storage/app/bsad/bsad.json`)
- `update_url` – dataset URL for `bs:update-data` (optional)
- `backup_on_update` – keep backups when updating (default: true)
- `locale` – default locale (`en` or `np`)
- `nepali_digits` – default digits output (true/false)

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

$ad = Bsad::bsToAd(2082, 1, 1);        // CarbonImmutable
echo $ad->toDateString();              // "2025-04-14" (example)
echo Bsad::bsToAdDateString(2082, 1, 1);
```

---

## Formatting

Formatting is provided by the `Formatter` service:

```php
use NobelzSushank\Bsad\Facades\Bsad;

$conv = app(\NobelzSushank\Bsad\Converters\BsadConverter::class);
$fmt  = app(\NobelzSushank\Bsad\Formatting\Formatter::class);

$bs = $conv->adToBs('2026-02-24'); // example

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

If you host a newer `bsad.json` somewhere (GitHub raw URL, S3, etc.), you can update the local dataset:

```bash
php artisan bs:update-data
```

Or override the URL/path on demand:

```bash
php artisan bs:update-data --url="https://example.com/bsad.json"
php artisan bs:update-data --path="/full/path/to/bsad.json"
```

### Important note for Octane / queue workers
If your app is long-running (Octane, queue workers), reload after updating:

- `php artisan octane:reload` (Octane)
- `php artisan queue:restart` (workers)

---

## Let Users Edit the Data Themselves

Because the active dataset is stored in **your app’s storage**:

- `storage/app/bsad/bsad.json`

Users can edit that file directly to patch a year/month if needed.

You can also let users point to a custom dataset path by changing `config/bsad.php`:

```php
'data_path' => storage_path('app/bsad/custom-bsad.json'),
```

---

## Dataset Format

`bsad.json` schema:

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
    "2000": [30,32,31,32,31,30,30,30,29,30,29,31],
    "2001": [31,31,32,31,31,31,30,29,30,29,30,30]
  }
}
```

- `years[BS_YEAR]` is an array of 12 integers: **days in each BS month**
- The converter uses an **anchor mapping** to compute offsets

---

## Error Handling / Supported Range

This package is **data-driven**, so supported years depend entirely on your dataset.

If you try to convert a year that’s not in `years`, you’ll get a runtime exception like:

> Unsupported BS year XXXX. Dataset supports YYYY–ZZZZ.

To support more years, update the dataset (via `composer update` in your data repo, or `bs:update-data`).

---

## Testing

This package is test-friendly (Orchestra Testbench). Run:

```bash
composer test
```

(If you add PHPUnit scripts, e.g. `"test": "vendor/bin/phpunit"`.)

---

## Extending / Roadmap Ideas

This package is intentionally structured so you can add features without breaking existing code:

- More format tokens (e.g. numeric month names in Nepali, custom patterns)
- Additional locales
- Caching precomputed year-prefix sums for faster large-range conversions
- Optional “override patches” layering multiple datasets (base + user patches)

---

## Security

If you discover a security issue, please open an issue or contact the maintainer.

---

## License

MIT — see `LICENSE`.
