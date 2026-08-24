<?php
/**
 * Backward-compatible composition point for LibreTT shortcode callbacks.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/trait-opentt-unified-shortcodes-portal.php';
require_once __DIR__ . '/trait-opentt-unified-shortcodes-matches.php';
require_once __DIR__ . '/trait-opentt-unified-shortcodes-entities.php';
require_once __DIR__ . '/trait-opentt-unified-shortcodes-stats.php';
require_once __DIR__ . '/trait-opentt-unified-shortcodes-shared.php';

trait OpenTT_Unified_Shortcodes_Trait
{
    use OpenTT_Unified_Shortcodes_Portal_Trait;
    use OpenTT_Unified_Shortcodes_Matches_Trait;
    use OpenTT_Unified_Shortcodes_Entities_Trait;
    use OpenTT_Unified_Shortcodes_Stats_Trait;
    use OpenTT_Unified_Shortcodes_Shared_Trait;
}
