<?php
/**
 * OpenTT shortcode compatibility adapters.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait OpenTT_Unified_Shortcodes_Shared_Trait
{
    private static function shortcode_title_html($title)
    {
        return OpenTT_Unified_Shortcode_UI_Service::shortcode_title_html($title);
    }

    private static function info_link_icon_html($icon_file_name, $fallback, $modifier = 'before')
    {
        return OpenTT_Unified_Shortcode_UI_Service::info_link_icon_html($icon_file_name, $fallback, $modifier, (string) self::$plugin_dir);
    }

    private static function normalize_phone_for_href($raw_phone)
    {
        return OpenTT_Unified_Readonly_Helpers::normalize_phone_for_href($raw_phone);
    }

    private static function format_phone_for_display($raw_phone)
    {
        return OpenTT_Unified_Readonly_Helpers::format_phone_for_display($raw_phone);
    }

    private static function competition_display_name($liga_slug, $sezona_slug)
    {
        return OpenTT_Unified_Competition_Presentation_Service::competition_display_name($liga_slug, $sezona_slug, [
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
        ]);
    }

    private static function season_display_name($sezona_slug)
    {
        return OpenTT_Unified_Competition_Presentation_Service::season_display_name($sezona_slug, [
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
        ]);
    }

    private static function competition_archive_url($liga_slug, $sezona_slug)
    {
        return OpenTT_Unified_Competition_Presentation_Service::competition_archive_url($liga_slug, $sezona_slug);
    }

    private static function match_venue_label($row)
    {
        return OpenTT_Unified_Match_Presentation_Service::match_venue_label($row);
    }

    private static function db_get_top_players_data($liga_slug, $sezona_slug = '', $max_kolo = null)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_top_players_data($liga_slug, $sezona_slug, $max_kolo);
    }

    private static function db_get_top_players_data_unfiltered($liga_slug, $sezona_slug = '', $max_kolo = null)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_top_players_data_unfiltered($liga_slug, $sezona_slug, $max_kolo);
    }

    private static function db_get_played_matches_count_by_club($liga_slug, $sezona_slug = '', $max_kolo = null)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_played_matches_count_by_club($liga_slug, $sezona_slug, $max_kolo);
    }

    private static function db_get_latest_competition_with_games()
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_latest_competition_with_games();
    }

    private static function db_get_latest_competition_for_player($player_id)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_latest_competition_for_player($player_id);
    }

    private static function db_get_latest_season_for_liga($liga_slug)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_latest_season_for_liga($liga_slug);
    }

    private static function db_get_latest_competition_for_club($club_id)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_latest_competition_for_club($club_id);
    }

    private static function db_get_recent_club_matches($club_id, $limit = 5)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_recent_club_matches($club_id, $limit);
    }

    private static function db_get_recent_club_matches_for_season($club_id, $limit = 5, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_recent_club_matches_for_season($club_id, $limit, $season_slug);
    }

    private static function db_get_club_player_ids_for_season($club_id, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_club_player_ids_for_season($club_id, $season_slug);
    }

    private static function db_get_player_season_club_history($player_id)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_player_season_club_history($player_id);
    }

    private static function season_sort_key($season_slug)
    {
        return OpenTT_Unified_Readonly_Helpers::season_sort_key($season_slug);
    }

    private static function build_player_stints($history)
    {
        return OpenTT_Unified_Player_History_Service::build_player_stints($history);
    }

    private static function db_get_player_stats($player_id, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_player_stats($player_id, $season_slug);
    }

    private static function db_get_player_season_options($player_id)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_player_season_options($player_id);
    }

    private static function db_get_club_season_options($club_id)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_club_season_options($club_id);
    }

    private static function db_get_latest_liga_for_player_and_season($player_id, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_latest_liga_for_player_and_season($player_id, $season_slug);
    }

    private static function db_get_latest_liga_for_club_and_season($club_id, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_latest_liga_for_club_and_season($club_id, $season_slug);
    }

    private static function db_get_club_team_stats($club_id, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_club_team_stats($club_id, $season_slug);
    }

    private static function db_get_club_season_best_player_by_success($club_id, $season_slug)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_club_season_best_player_by_success($club_id, $season_slug);
    }

    private static function db_get_competition_club_ids($liga_slug, $sezona_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_competition_club_ids($liga_slug, $sezona_slug);
    }

    private static function db_build_standings_for_competition($liga_slug, $sezona_slug = '', $max_kolo = null)
    {
        return OpenTT_Unified_Shortcode_Standings_Service::db_build_standings_for_competition($liga_slug, $sezona_slug, $max_kolo, [
            'db_get_matches' => static function ($args) {
                return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_matches($args);
            },
            'get_competition_rule_data' => static function ($liga_slug_arg, $sezona_slug_arg = '') {
                return self::get_competition_rule_data($liga_slug_arg, $sezona_slug_arg);
            },
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
        ]);
    }

    private static function find_club_rank_in_standings($standings, $club_id)
    {
        return OpenTT_Unified_Shortcode_Standings_Service::find_club_rank_in_standings($standings, $club_id);
    }

    private static function build_standings_window_around_club($standings, $club_rank, $radius = 2)
    {
        return OpenTT_Unified_Shortcode_Standings_Service::build_standings_window_around_club($standings, $club_rank, $radius);
    }

    private static function format_percentage_value($value)
    {
        return OpenTT_Unified_Shortcode_Standings_Service::format_percentage_value($value);
    }

    private static function db_get_player_mvp_count($player_id, $season_slug = '')
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_player_mvp_count($player_id, $season_slug);
    }

    private static function db_get_match_mvp_player_id($match_id)
    {
        return OpenTT_Unified_Shortcode_Stats_Query_Service::db_get_match_mvp_player_id($match_id);
    }

    private static function render_top_player_card_list($igrac_id, $rank, $info, $highlight = false)
    {
        return OpenTT_Unified_Shortcode_UI_Service::render_top_player_card_list($igrac_id, $rank, $info, $highlight, [
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
        ]);
    }

    private static function build_match_query_args($atts)
    {
        return OpenTT_Unified_Shortcode_Match_Query_Service::build_match_query_args($atts, [
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'parse_legacy_liga_sezona' => static function ($liga, $sezona) {
                return self::parse_legacy_liga_sezona($liga, $sezona);
            },
        ]);
    }

    private static function db_get_match_by_legacy_id($legacy_id)
    {
        return OpenTT_Unified_Shortcode_Match_Query_Service::db_get_match_by_legacy_id($legacy_id);
    }

    private static function render_matches_grid_html($rows, $columns, $with_kolo_attr)
    {
        return OpenTT_Unified_Grid_Render_Service::render_matches_grid_html($rows, $columns, $with_kolo_attr, [
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
            'kolo_heading_label' => static function ($slug, $round_no = null) {
                return self::kolo_heading_label($slug, $round_no);
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
            'parse_match_timestamp' => static function ($match_date, $end_of_day_if_midnight = false) {
                return self::parse_match_timestamp($match_date, $end_of_day_if_midnight);
            },
            'is_match_live' => static function ($row) {
                return self::is_match_live($row);
            },
            'render_team_html' => static function ($club_id, $score, $is_winner, $show_score = true, $fallback_score_label = '') {
                return self::render_team_html($club_id, $score, $is_winner, $show_score, $fallback_score_label);
            },
        ]);
    }

    private static function render_clubs_grid_html($rows, $columns, $with_attrs)
    {
        return OpenTT_Unified_Grid_Render_Service::render_clubs_grid_html($rows, $columns, $with_attrs);
    }

    private static function club_fallback_image_url()
    {
        return OpenTT_Unified_Media_Service::club_fallback_image_url(self::$plugin_dir, self::$plugin_file);
    }

    private static function player_fallback_image_url()
    {
        return OpenTT_Unified_Media_Service::player_fallback_image_url(self::$plugin_dir, self::$plugin_file);
    }

    private static function club_logo_url($club_id, $size = 'thumbnail')
    {
        return OpenTT_Unified_Media_Service::club_logo_url($club_id, $size, [
            'club_fallback_image_url' => static function () {
                return self::club_fallback_image_url();
            },
        ]);
    }

    private static function resolve_club_id_from_value($value)
    {
        return OpenTT_Unified_Media_Service::resolve_club_id_from_value($value);
    }

    private static function club_logo_html($club_id, $size = 'thumbnail', $attr = [])
    {
        return OpenTT_Unified_Media_Service::club_logo_html($club_id, $size, $attr, [
            'club_fallback_image_url' => static function () {
                return self::club_fallback_image_url();
            },
        ]);
    }

    private static function render_team_html($club_id, $score, $is_winner, $show_score = true, $fallback_score_label = '')
    {
        return OpenTT_Unified_Entity_Presentation_Service::render_team_html($club_id, $score, $is_winner, $show_score, $fallback_score_label, [
            'club_logo_html' => static function ($club_id_arg, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id_arg, $size, $attr);
            },
        ]);
    }

    private static function display_match_time($match_date)
    {
        return OpenTT_Unified_Match_Presentation_Service::display_match_time($match_date);
    }

    private static function kolo_heading_label($kolo_slug, $kolo_no = null)
    {
        return OpenTT_Unified_Competition_Presentation_Service::kolo_heading_label($kolo_slug, $kolo_no, [
            'extract_round_no' => static function ($slug) {
                return self::extract_round_no($slug);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
        ]);
    }

    private static function is_match_live($row)
    {
        return OpenTT_Unified_Match_Presentation_Service::is_match_live($row);
    }

    private static function parse_match_timestamp($match_date, $end_of_day_if_midnight = false)
    {
        return OpenTT_Unified_Match_Presentation_Service::parse_match_timestamp($match_date, $end_of_day_if_midnight);
    }

    private static function match_permalink($row)
    {
        return OpenTT_Unified_Match_Presentation_Service::match_permalink($row, [
            'is_legacy_match_cpt_enabled' => static function () {
                return self::is_legacy_match_cpt_enabled();
            },
        ]);
    }

    private static function display_match_date($match_date)
    {
        return OpenTT_Unified_Match_Presentation_Service::display_match_date($match_date);
    }

    private static function display_match_date_long($match_date)
    {
        return OpenTT_Unified_Match_Presentation_Service::display_match_date_long($match_date);
    }

    private static function kolo_name_from_slug($slug)
    {
        return OpenTT_Unified_Competition_Presentation_Service::kolo_name_from_slug($slug, [
            'extract_round_no' => static function ($kolo_slug) {
                return self::extract_round_no($kolo_slug);
            },
        ]);
    }

    private static function extract_round_no($kolo_slug)
    {
        return OpenTT_Unified_Readonly_Helpers::extract_round_no($kolo_slug);
    }

    private static function render_lp2_player($player_id)
    {
        return OpenTT_Unified_Entity_Presentation_Service::render_lp2_player($player_id, [
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
        ]);
    }

    private static function render_klub_card_html($klub_id)
    {
        return OpenTT_Unified_Entity_Presentation_Service::render_klub_card_html($klub_id, [
            'club_logo_html' => static function ($club_id_arg, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id_arg, $size, $attr);
            },
        ]);
    }

    private static function current_match_context()
    {
        return OpenTT_Unified_Match_Context_Service::current_match_context(
            self::$virtual_match_row,
            static function ($legacy_id) {
                return self::db_get_match_by_legacy_id($legacy_id);
            }
        );
    }

    public static function get_template_match_context()
    {
        $ctx = self::current_match_context();
        return OpenTT_Unified_Match_Context_Service::get_template_match_context($ctx, [
            'display_match_date' => static function ($match_date) {
                return self::display_match_date($match_date);
            },
            'kolo_name_from_slug' => static function ($slug) {
                return self::kolo_name_from_slug($slug);
            },
            'match_permalink' => static function ($row) {
                return self::match_permalink($row);
            },
        ]);
    }

    private static function get_match_block_template()
    {
        return OpenTT_Unified_Match_Context_Service::get_match_block_template(self::MATCH_BLOCK_TEMPLATE_SLUG);
    }

}

