<?php
/**
 * OpenTT shortcode compatibility adapters.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait OpenTT_Unified_Shortcodes_Entities_Trait
{
    public static function shortcode_featured_player($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\FeaturedPlayerShortcode::render($atts, [
            'db_get_top_players_data' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_get_top_players_data($liga_slug, $sezona_slug, $max_kolo);
            },
            'db_get_top_players_data_unfiltered' => static function ($liga_slug, $sezona_slug = '', $max_kolo = null) {
                return self::db_get_top_players_data_unfiltered($liga_slug, $sezona_slug, $max_kolo);
            },
            'db_get_latest_competition_for_club' => static function ($club_id) {
                return self::db_get_latest_competition_for_club($club_id);
            },
            'db_get_latest_competition_for_player' => static function ($player_id) {
                return self::db_get_latest_competition_for_player($player_id);
            },
            'db_get_latest_season_for_liga' => static function ($liga_slug) {
                return self::db_get_latest_season_for_liga($liga_slug);
            },
            'db_get_latest_competition_with_games' => static function () {
                return self::db_get_latest_competition_with_games();
            },
            'get_player_club_id' => static function ($player_id) {
                return self::get_player_club_id($player_id);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_clubs_grid($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ClubsGridShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'db_get_latest_competition_for_club' => static function ($club_id) {
                return self::db_get_latest_competition_for_club($club_id);
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'render_clubs_grid_html' => static function ($rows, $columns, $with_attrs) {
                return self::render_clubs_grid_html($rows, $columns, $with_attrs);
            },
        ]);
    }

    public static function shortcode_show_players($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ShowPlayersShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
            'db_get_club_player_ids_for_season' => static function ($club_id, $season_slug = '') {
                return self::db_get_club_player_ids_for_season($club_id, $season_slug);
            },
            'db_get_club_season_options' => static function ($club_id) {
                return self::db_get_club_season_options($club_id);
            },
        ]);
    }

    public static function shortcode_club_news($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ClubNewsShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_player_news($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\PlayerNewsShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_related_posts($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\RelatedPostsShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_player_transfers($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\PlayerTransfersShortcode::render($atts, [
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
            'db_get_player_season_club_history' => static function ($player_id) {
                return self::db_get_player_season_club_history($player_id);
            },
            'build_player_stints' => static function ($history) {
                return self::build_player_stints($history);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
        ]);
    }

    public static function shortcode_club_featured($atts = [])
    {
        $atts = shortcode_atts([
            'klub' => '',
            'id' => '',
            'height' => '',
            'link' => 'false',
        ], (array) $atts, 'opentt_club_featured');

        $club_id = 0;
        $id_raw = trim((string) ($atts['id'] ?? ''));
        if ($id_raw !== '' && is_numeric($id_raw)) {
            $club_id = intval($id_raw);
            if ($club_id > 0 && get_post_type($club_id) !== 'klub') {
                $club_id = 0;
            }
        }

        if ($club_id <= 0) {
            $club_id = self::resolve_club_id_from_value((string) ($atts['klub'] ?? ''));
        }

        if ($club_id <= 0 && is_singular('klub')) {
            $club_id = intval(get_the_ID());
        }

        if ($club_id <= 0) {
            $ctx = self::current_match_context();
            if (is_array($ctx) && !empty($ctx['db_row'])) {
                $club_id = intval($ctx['db_row']->home_club_post_id ?? 0);
            }
        }

        if ($club_id <= 0 || get_post_type($club_id) !== 'klub') {
            return '';
        }

        $image_id = intval(get_post_meta($club_id, 'opentt_club_featured_image_id', true));
        $image_url = $image_id > 0 ? wp_get_attachment_image_url($image_id, 'full') : '';
        if (!is_string($image_url)) {
            $image_url = '';
        }
        if ($image_url === '') {
            return '';
        }

        $focus_x_raw = floatval(get_post_meta($club_id, 'opentt_club_featured_focus_x', true));
        $focus_y_raw = floatval(get_post_meta($club_id, 'opentt_club_featured_focus_y', true));
        $focus_x = ($focus_x_raw >= 0 && $focus_x_raw <= 100) ? $focus_x_raw : 50.0;
        $focus_y = ($focus_y_raw >= 0 && $focus_y_raw <= 100) ? $focus_y_raw : 50.0;

        $title = (string) get_the_title($club_id);
        $raw_height = intval($atts['height'] ?? 0);
        $height = $raw_height > 0 ? max(1, min(2000, $raw_height)) : 0;
        $style_parts = [
            '--opentt-club-featured-focus-x:' . $focus_x . '%',
            '--opentt-club-featured-focus-y:' . $focus_y . '%',
        ];
        if ($height > 0) {
            $style_parts[] = '--opentt-club-featured-height:' . $height . 'px';
        }
        $style_attr = ' style="' . esc_attr(implode(';', $style_parts)) . '"';
        $wrap_class = 'opentt-club-featured-wrap' . ($height > 0 ? ' is-fixed-height' : '');
        $link_raw = strtolower(trim((string) ($atts['link'] ?? 'false')));
        $link_enabled = !in_array($link_raw, ['0', 'false', 'no', 'off'], true);
        $url = get_permalink($club_id);

        ob_start();
        echo '<div class="' . esc_attr($wrap_class) . '"' . $style_attr . '>';
        if ($link_enabled && is_string($url) && $url !== '') {
            echo '<a class="opentt-club-featured-media" href="' . esc_url($url) . '">';
        } else {
            echo '<div class="opentt-club-featured-media">';
        }
        echo '<img class="opentt-club-featured-image" src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '">';
        if ($link_enabled && is_string($url) && $url !== '') {
            echo '</a>';
        } else {
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public static function shortcode_club_info($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ClubInfoShortcode::render($atts, [
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'db_get_latest_competition_for_club' => static function ($club_id) {
                return self::db_get_latest_competition_for_club($club_id);
            },
            'parse_legacy_liga_sezona' => static function ($liga_slug, $sezona_slug = '') {
                return self::parse_legacy_liga_sezona($liga_slug, $sezona_slug);
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'get_competition_rule_data' => static function ($liga_slug, $sezona_slug = '') {
                return self::get_competition_rule_data($liga_slug, $sezona_slug);
            },
            'competition_federation_data' => static function ($code) {
                return self::competition_federation_data($code);
            },
            'info_link_icon_html' => static function ($icon_file_name, $fallback, $modifier = 'before') {
                return self::info_link_icon_html($icon_file_name, $fallback, $modifier);
            },
            'normalize_phone_for_href' => static function ($raw_phone) {
                return self::normalize_phone_for_href($raw_phone);
            },
            'format_phone_for_display' => static function ($raw_phone) {
                return self::format_phone_for_display($raw_phone);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_club_card($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\ClubCardShortcode::render($atts, [
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'db_get_club_season_options' => static function ($club_id) {
                return self::db_get_club_season_options($club_id);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

    public static function shortcode_player_info($atts = [])
    {
        return \OpenTT\Unified\WordPress\Shortcodes\PlayerInfoShortcode::render($atts, [
            'player_fallback_image_url' => static function () {
                return self::player_fallback_image_url();
            },
            'get_player_club_id' => static function ($player_id) {
                return self::get_player_club_id($player_id);
            },
            'club_logo_html' => static function ($club_id, $size = 'thumbnail', $attr = []) {
                return self::club_logo_html($club_id, $size, $attr);
            },
            'country_label_by_code' => static function ($country_code) {
                return OpenTT_Unified_Core::country_label_by_code($country_code);
            },
            'country_flag_emoji' => static function ($country_code) {
                return OpenTT_Unified_Core::country_flag_emoji($country_code);
            },
            'current_archive_context' => static function () {
                return self::current_archive_context();
            },
            'current_match_context' => static function () {
                return self::current_match_context();
            },
            'slug_to_title' => static function ($slug) {
                return self::slug_to_title($slug);
            },
            'season_display_name' => static function ($sezona_slug) {
                return self::season_display_name($sezona_slug);
            },
            'shortcode_title_html' => static function ($title) {
                return self::shortcode_title_html($title);
            },
        ]);
    }

}

