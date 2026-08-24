<?php

define('ABSPATH', __DIR__ . '/wordpress-placeholder/');

require_once dirname(__DIR__) . '/includes/class-opentt-unified-core.php';

if (!class_exists('OpenTT_Unified_Core')) {
    fwrite(STDERR, "Core class did not load.\n");
    exit(1);
}

$requiredCallbacks = [
    'handle_frontend_search_ajax',
    'shortcode_matches_grid',
    'shortcode_standings_table',
    'shortcode_show_players',
];

foreach ($requiredCallbacks as $callback) {
    if (!is_callable(['OpenTT_Unified_Core', $callback])) {
        fwrite(STDERR, "Missing core callback: {$callback}\n");
        exit(1);
    }
}

echo "Core composition checks passed.\n";
