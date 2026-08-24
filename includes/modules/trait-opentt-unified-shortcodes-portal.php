<?php
/**
 * OpenTT shortcode compatibility adapters.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait OpenTT_Unified_Shortcodes_Portal_Trait
{
    public static function shortcode_auth($atts = [])
    {
        unset($atts);
        return \OpenTT\Unified\WordPress\UserPortalManager::renderAuthShortcode();
    }

    public static function shortcode_auth_menu($atts = [])
    {
        unset($atts);
        return \OpenTT\Unified\WordPress\UserPortalManager::renderAuthMenuShortcode();
    }

    public static function shortcode_profile($atts = [])
    {
        unset($atts);
        return \OpenTT\Unified\WordPress\UserPortalManager::renderProfileShortcode();
    }

    public static function shortcode_search($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\SearchShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'db_get_latest_liga_for_club_and_season' => static function ($club_id, $season_slug = '') {
                return self::db_get_latest_liga_for_club_and_season($club_id, $season_slug);
            },
        ]);
    }

}

