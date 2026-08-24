<?php
/**
 * OpenTT shortcode compatibility adapters.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait OpenTT_Unified_Shortcodes_Stats_Trait
{
    public static function shortcode_standings_table($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\StandingsTableShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'db_get_match_by_legacy_id' => static function ($legacy_id) {
                return self::db_get_match_by_legacy_id($legacy_id);
            },
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
            'db_get_latest_liga_for_club' => static function ($club_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_latest_liga_for_club($club_id);
            },
            'db_table' => static function ($table_alias) {
                return OpenTT_Unified_Core::db_table($table_alias);
            },
            'table_exists' => static function ($table_name) {
                return self::table_exists($table_name);
            },
            'db_get_matches' => static function ($args) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_matches($args);
            },
            'get_competition_rule_data' => static function ($liga_slug, $sezona_slug = '') {
                return self::get_competition_rule_data($liga_slug, $sezona_slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
        ]);
    }

    public static function shortcode_standings_short($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\StandingsShortShortcode::render($atts, [
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'db_build_standings_for_competition' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_build_standings_for_competition($liga_slug, $sezona_slug, $max_kolo);
            },
            'find_club_rank_in_standings' => static function ($standings, $club_id) {
                return self::find_club_rank_in_standings($standings, $club_id);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'db_get_latest_liga_for_club' => static function ($club_id) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_latest_liga_for_club($club_id);
            },
            'db_get_latest_liga_for_club_and_season' => static function ($club_id, $season_slug = '') {
                return self::db_get_latest_liga_for_club_and_season($club_id, $season_slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
        ]);
    }

    public static function shortcode_club_form($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ClubFormShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'db_get_recent_club_matches' => static function ($club_id, $limit) {
                return self::db_get_recent_club_matches($club_id, $limit);
            },
            'db_get_recent_club_matches_for_season' => static function ($club_id, $limit, $season_slug = '') {
                return self::db_get_recent_club_matches_for_season($club_id, $limit, $season_slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'display_match_date' => static function ($match_date) {
                return self::display_match_date($match_date);
            },
            'match_permalink' => static function ($row) {
                return self::match_permalink($row);
            },
        ]);
    }

    public static function shortcode_player_stats($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\PlayerStatsShortcode::render($atts, [
            'db_get_player_season_options' => static function ($player_id) {
                return self::db_get_player_season_options($player_id);
            },
            'db_get_player_stats' => static function ($player_id, $season_slug = '') {
                return self::db_get_player_stats($player_id, $season_slug);
            },
            'db_get_player_mvp_count' => static function ($player_id, $season_slug = '') {
                return self::db_get_player_mvp_count($player_id, $season_slug);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'db_get_latest_competition_for_player' => static function ($player_id) {
                return self::db_get_latest_competition_for_player($player_id);
            },
            'db_get_latest_liga_for_player_and_season' => static function ($player_id, $season_slug = '') {
                return self::db_get_latest_liga_for_player_and_season($player_id, $season_slug);
            },
            'db_get_top_players_data' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_get_top_players_data($liga_slug, $sezona_slug, $max_kolo);
            },
            'render_top_player_card_list' => static function ($player_id, $rank, $info, $highlight = false) {
                return self::render_top_player_card_list($player_id, $rank, $info, $highlight);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_team_stats($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\TeamStatsShortcode::render($atts, [
            'db_get_club_season_options' => static function ($club_id) {
                return self::db_get_club_season_options($club_id);
            },
            'db_get_club_team_stats' => static function ($club_id, $season_slug = '') {
                return self::db_get_club_team_stats($club_id, $season_slug);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'db_get_latest_competition_for_club' => static function ($club_id) {
                return self::db_get_latest_competition_for_club($club_id);
            },
            'db_get_club_season_best_player_by_success' => static function ($club_id, $season_slug) {
                return self::db_get_club_season_best_player_by_success($club_id, $season_slug);
            },
            'db_get_latest_liga_for_club_and_season' => static function ($club_id, $season_slug = '') {
                return self::db_get_latest_liga_for_club_and_season($club_id, $season_slug);
            },
            'db_build_standings_for_competition' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_build_standings_for_competition($liga_slug, $sezona_slug, $max_kolo);
            },
            'find_club_rank_in_standings' => static function ($standings, $club_id) {
                return self::find_club_rank_in_standings($standings, $club_id);
            },
            'build_standings_window_around_club' => static function ($standings, $club_rank, $radius = 2) {
                return self::build_standings_window_around_club($standings, $club_rank, $radius);
            },
            'competition_display_name' => static function ($liga_slug, $sezona_slug) {
                return self::competition_display_name($liga_slug, $sezona_slug);
            },
            'get_competition_rule_data' => static function ($liga_slug, $sezona_slug = '') {
                return self::get_competition_rule_data($liga_slug, $sezona_slug);
            },
            'format_percentage_value' => static function ($value) {
                return self::format_percentage_value($value);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
        ]);
    }

    public static function shortcode_global_stats($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\GlobalStatsShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_competition_info($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\CompetitionInfoShortcode::render($atts, [
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'db_table' => static function ($table_alias) {
                return OpenTT_Unified_Core::db_table($table_alias);
            },
            'table_exists' => static function ($table_name) {
                return self::table_exists($table_name);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'get_competition_rule_data' => static function ($liga_slug, $sezona_slug = '') {
                return self::get_competition_rule_data($liga_slug, $sezona_slug);
            },
            'competition_federation_data' => static function ($code) {
                return self::competition_federation_data($code);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_competitions_grid($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\CompetitionsGridShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'season_sort_key' => static function ($season_slug) {
                return self::season_sort_key($season_slug);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'competition_archive_url' => static function ($liga_slug, $sezona_slug) {
                return self::competition_archive_url($liga_slug, $sezona_slug);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'db_get_competition_club_ids' => static function ($liga_slug, $sezona_slug = '') {
                return self::db_get_competition_club_ids($liga_slug, $sezona_slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
        ]);
    }

    public static function shortcode_top_players_list($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\TopPlayersListShortcode::render($atts, [
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'db_table' => static function ($table_alias) {
                return OpenTT_Unified_Core::db_table($table_alias);
            },
            'table_exists' => static function ($table_name) {
                return self::table_exists($table_name);
            },
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'db_get_latest_competition_for_player' => static function ($player_id) {
                return self::db_get_latest_competition_for_player($player_id);
            },
            'db_get_latest_competition_with_games' => static function () {
                return self::db_get_latest_competition_with_games();
            },
            'db_get_top_players_data' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_get_top_players_data($liga_slug, $sezona_slug, $max_kolo);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'render_top_player_card_list' => static function ($player_id, $rank, $info, $highlight = false) {
                return self::render_top_player_card_list($player_id, $rank, $info, $highlight);
            },
        ]);
    }

}

