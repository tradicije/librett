<?php
/**
 * OpenTT shortcode compatibility adapters.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait OpenTT_Unified_Shortcodes_Matches_Trait
{
    public static function shortcode_matches_grid($atts)
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchesGridShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'build_match_query_args' => static function ($args) {
                return self::build_match_query_args($args);
            },
            'db_get_matches' => static function ($args) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_matches($args);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
            'render_matches_grid_html' => static function ($rows, $columns, $with_kolo_attr) {
                return self::render_matches_grid_html($rows, $columns, $with_kolo_attr);
            },
        ]);
    }

    public static function shortcode_matches_grid_alt($atts)
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchesGridAltShortcode::render($atts, [
            'render_default' => static function ($inner_atts) {
                return self::shortcode_matches_grid($inner_atts);
            },
        ]);
    }

    public static function shortcode_matches($atts)
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchesShortcode::render($atts, [
            'render_matches_grid' => static function ($inner_atts) {
                return self::shortcode_matches_grid($inner_atts);
            },
            'render_matches_list' => static function ($inner_atts) {
                return self::shortcode_matches_list($inner_atts);
            },
        ]);
    }

    public static function shortcode_matches_list($atts)
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchesListShortcode::render($atts, [
            'build_match_query_args' => static function ($args) {
                return self::build_match_query_args($args);
            },
            'db_get_matches' => static function ($args) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_matches($args);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'display_match_date' => static function ($match_date) {
                return self::display_match_date($match_date);
            },
            'display_match_time' => static function ($match_date) {
                return self::display_match_time($match_date);
            },
            'match_permalink' => static function ($row) {
                return self::match_permalink($row);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
        ]);
    }

    public static function shortcode_match_id($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchIdShortcode::render($atts, [
            'db_get_match_by_id' => static function ($id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_match_by_id($id);
            },
            'build_match_query_args' => static function ($args) {
                return self::build_match_query_args($args);
            },
            'db_get_matches' => static function ($args) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_matches($args);
            },
            'display_match_date' => static function ($match_date) {
                return self::display_match_date($match_date);
            },
            'display_match_time' => static function ($match_date) {
                return self::display_match_time($match_date);
            },
            'match_permalink' => static function ($row) {
                return self::match_permalink($row);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'parse_match_timestamp' => static function ($match_date, $end_of_day_if_midnight = false) {
                return self::parse_match_timestamp($match_date, $end_of_day_if_midnight);
            },
        ]);
    }

    public static function shortcode_featured_match($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\FeaturedMatchShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'match_permalink' => static function ($row) {
                return self::match_permalink($row);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'db_build_standings_for_competition' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_build_standings_for_competition($liga_slug, $sezona_slug, $max_kolo);
            },
            'build_match_query_args' => static function ($args) {
                return self::build_match_query_args($args);
            },
        ]);
    }

    public static function shortcode_games_list($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\GamesListShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'db_get_match_by_id' => static function ($id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_match_by_id($id);
            },
            'db_get_games_for_match_id' => static function ($match_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_games_for_match_id($match_id);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'get_competition_rule_data' => static function ($liga_slug, $sezona_slug = '') {
                return self::get_competition_rule_data($liga_slug, $sezona_slug);
            },
            'db_get_sets_for_game_id' => static function ($game_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_sets_for_game_id($game_id);
            },
            'render_lp2_player' => static function ($player_id) {
                return self::render_lp2_player($player_id);
            },
            'players_for_club_options' => static function ($club_id) {
                return OpenTT_Unified_Admin_Readonly_Helpers::players_for_club_options($club_id);
            },
            'turnstile_enabled' => static function () {
                return OpenTT_Unified_Core::is_turnstile_enabled();
            },
            'turnstile_site_key' => static function () {
                return OpenTT_Unified_Core::turnstile_site_key();
            },
            'games_submit_page_url' => static function ($match_id) {
                return OpenTT_Unified_Core::games_submit_page_url($match_id);
            },
            'render_match_teams_for_row' => static function ($row) {
                if (!is_object($row)) {
                    return '';
                }
                $ctx = [
                    'db_row' => $row,
                    'legacy_id' => intval($row->legacy_post_id ?? 0),
                ];
                return \OpenTT\Unified\WordPress\Shortcodes\ShowMatchTeamsShortcode::render([], [
                    'current_match_context' => static function () use ($ctx) {
                        return $ctx;
                    },
                    'competition_display_name' => static function ($liga_slug, $sezona_slug) {
                        return self::competition_display_name($liga_slug, $sezona_slug);
                    },
                    'competition_archive_url' => static function ($liga_slug, $sezona_slug) {
                        return self::competition_archive_url($liga_slug, $sezona_slug);
                    },
                    'kolo_name_from_slug' => static function ($slug) {
                        return self::kolo_name_from_slug($slug);
                    },
                    'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                        return self::club_logo_html($club_id, $size, $attr);
                    },
                    'display_match_date' => static function ($match_date) {
                        return self::display_match_date($match_date);
                    },
                    'match_venue_label' => static function ($row_in) {
                        return self::match_venue_label($row_in);
                    },
                    'shortcode_title_html' => static function ($title) {
                        return '';
                    },
                ]);
            },
        ]);
    }

    public static function shortcode_h2h($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\H2hShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'db_get_h2h_matches' => static function ($current_match_db_id, $home_club_id, $away_club_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_h2h_matches($current_match_db_id, $home_club_id, $away_club_id);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'club_logo_url' => static function ($club_id, $size = 'thumbnail') {
                return self::club_logo_url($club_id, $size);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'display_match_date_long' => static function ($match_date) {
                return self::display_match_date_long($match_date);
            },
            'match_permalink' => static function ($row) {
                return self::match_permalink($row);
            },
        ]);
    }

    public static function shortcode_mvp($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MvpShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'db_get_games_for_match_id' => static function ($match_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_games_for_match_id($match_id);
            },
            'db_get_sets_for_game_id' => static function ($game_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_sets_for_game_id($game_id);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
            'club_logo_url' => static function ($club_id, $size = 'thumbnail') {
                return self::club_logo_url($club_id, $size);
            },
        ]);
    }

    public static function shortcode_match_report($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchReportShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_match_video($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchVideoShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_match_teams_short($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\MatchTeamsShortShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_show_home_club($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ShowHomeClubShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'render_klub_card_html' => static function ($club_id) {
                return self::render_klub_card_html($club_id);
            },
        ]);
    }

    public static function shortcode_show_away_club($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ShowAwayClubShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'render_klub_card_html' => static function ($club_id) {
                return self::render_klub_card_html($club_id);
            },
        ]);
    }

    public static function shortcode_show_club_by_name($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ShowClubByNameShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'render_klub_card_html' => static function ($club_id) {
                return self::render_klub_card_html($club_id);
            },
        ]);
    }

    public static function shortcode_show_match_teams($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ShowMatchTeamsShortcode::render($atts, [
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'competition_display_name' => static function ($liga_slug, $sezona_slug) {
                return self::competition_display_name($liga_slug, $sezona_slug);
            },
            'competition_archive_url' => static function ($liga_slug, $sezona_slug) {
                return self::competition_archive_url($liga_slug, $sezona_slug);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'display_match_date' => static function ($match_date) {
                return self::display_match_date($match_date);
            },
            'match_venue_label' => static function ($row) {
                return self::match_venue_label($row);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

}

