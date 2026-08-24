# LibreTT Performance Baseline

Record measurements on the same staging installation, dataset, user role, theme,
PHP version, and cache state before and after a refactor. Use Query Monitor and
repeat every request three times after one warm-up request.

## Environment

- Date / commit:
- WordPress / PHP / database:
- Theme:
- Dataset size (matches / games / players / clubs):
- Object/page cache state:

## Measurements

| Scenario | URL or action | SQL queries | Duplicate queries | DB time | Page/AJAX time | Peak memory |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| Matches grid | | | | | | |
| Matches list on a single club with explicit season | | | | | | |
| Full standings | | | | | | |
| Club players/team statistics/form | | | | | | |
| Single DB match | | | | | | |
| Empty search discovery AJAX | | | | | | |
| Search AJAX with a representative Serbian query | | | | | | |
| Admin match edit | | | | | | |

## Acceptance rule

- Compare medians of the three measured requests.
- A refactor must not add plugin-owned queries or cause a material time regression.
- Optimize only a hotspot visible in this baseline. Record the query/call site and
  before/after measurements below.

## Confirmed optimization

- Hotspot:
- Cause:
- Change:
- Before:
- After:
