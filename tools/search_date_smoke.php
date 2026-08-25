<?php

require_once dirname(__DIR__) . '/src/WordPress/FrontendSearchText.php';
require_once dirname(__DIR__) . '/src/WordPress/FrontendSearchDateParser.php';

use OpenTT\Unified\WordPress\FrontendSearchDateParser;

$checks = [
    [FrontendSearchDateParser::localDate('12.09.2026'), '2026-09-12', 'Local date'],
    [FrontendSearchDateParser::localDate('29.02.2024'), '2024-02-29', 'Leap-year date'],
    [FrontendSearchDateParser::localDate('29.02.2025'), '', 'Invalid leap-year date'],
    [FrontendSearchDateParser::localDate('31.13.2026'), '', 'Invalid month'],
    [FrontendSearchDateParser::monthYearRange('februar', 2024), ['from' => '2024-02-01', 'to' => '2024-02-29'], 'Leap-year month'],
    [FrontendSearchDateParser::monthYearRange('septembar', 2026), ['from' => '2026-09-01', 'to' => '2026-09-30'], 'Thirty-day month'],
    [FrontendSearchDateParser::monthYearRange('nepoznat', 2026), [], 'Unknown month'],
    [FrontendSearchDateParser::monthYearRange('januar', 1999), [], 'Unsupported year'],
];

foreach ($checks as [$actual, $expected, $label]) {
    if ($actual !== $expected) {
        fwrite(STDERR, $label . " failed.\n");
        exit(1);
    }
}

echo "Frontend search date checks passed.\n";
