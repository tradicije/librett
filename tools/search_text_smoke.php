<?php

require_once dirname(__DIR__) . '/src/WordPress/FrontendSearchText.php';

use OpenTT\Unified\WordPress\FrontendSearchText;

$checks = [
    [FrontendSearchText::fold('Čačak Đorđe'), 'cacak djordje', 'Latin folding'],
    [FrontendSearchText::fold('Љ Њ Џ'), 'lj nj dz', 'Cyrillic digraph folding'],
    [FrontendSearchText::tokenizeUnicode('Prva liga 2026/27'), ['Prva', 'liga', '2026', '27'], 'Unicode tokens'],
    [FrontendSearchText::score('STK Crvena zvezda', 'crvena') > 0, true, 'Substring score'],
    [FrontendSearchText::score('Aleksandar', 'Aleksnder') > 0, true, 'Fuzzy score'],
    [FrontendSearchText::score('', 'liga'), 0, 'Empty score'],
];

foreach ($checks as [$actual, $expected, $label]) {
    if ($actual !== $expected) {
        fwrite(STDERR, $label . " failed.\n");
        exit(1);
    }
}

echo "Frontend search text checks passed.\n";
