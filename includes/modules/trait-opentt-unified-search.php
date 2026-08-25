<?php
/**
 * Compatibility implementation for frontend search orchestration.
 * New isolated search responsibilities should live in PSR-4 services.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait OpenTT_Unified_Search_Trait
{
    private static function normalize_search_context($context)
    {
        if (!is_array($context)) {
            $context = [];
        }

        return [
            'type' => sanitize_key((string) ($context['type'] ?? 'generic')),
            'match_id' => max(0, intval($context['match_id'] ?? 0)),
            'liga_slug' => sanitize_title((string) ($context['liga_slug'] ?? '')),
            'sezona_slug' => sanitize_title((string) ($context['sezona_slug'] ?? '')),
            'home_club_id' => max(0, intval($context['home_club_id'] ?? 0)),
            'away_club_id' => max(0, intval($context['away_club_id'] ?? 0)),
        ];
    }

    private static function build_search_groups($query, $limit, array $context)
    {
        $query = trim((string) $query);
        if ($query === '') {
            return [];
        }

        $competition_club_ids = self::search_competition_club_ids((string) $context['liga_slug'], (string) $context['sezona_slug']);
        $match_player_ids = self::search_match_player_ids(intval($context['match_id']));

        $groups = [];

        $players = self::search_players_group($query, $limit, $context, $competition_club_ids, $match_player_ids);
        if (!empty($players)) {
            $groups[] = ['key' => 'players', 'label' => 'Igrači', 'items' => $players];
        }

        $clubs = self::search_clubs_group($query, $limit, $context, $competition_club_ids);
        if (!empty($clubs)) {
            $groups[] = ['key' => 'clubs', 'label' => 'Klubovi', 'items' => $clubs];
        }

        $leagues = self::search_leagues_group($query, $limit, $context);
        if (!empty($leagues)) {
            $groups[] = ['key' => 'leagues', 'label' => 'Lige i sezone', 'items' => $leagues];
        }

        $matches = self::search_matches_group($query, $limit, $context);
        if (!empty($matches)) {
            $groups[] = ['key' => 'matches', 'label' => 'Utakmice', 'items' => $matches];
        }

        return $groups;
    }

    private static function search_query_suggestions(array $context)
    {
        $club_names = [];
        $club_ids = array_values(array_unique(array_filter([
            intval($context['home_club_id'] ?? 0),
            intval($context['away_club_id'] ?? 0),
        ])));

        foreach ($club_ids as $club_id) {
            $name = self::search_normalize_club_name((string) get_the_title($club_id));
            if ($name === '') {
                continue;
            }
            $club_names[] = $name;
        }

        if (count($club_names) < 2) {
            foreach (['Bubušinac', 'Napad', 'Lešak'] as $fallback_name) {
                if (!in_array($fallback_name, $club_names, true)) {
                    $club_names[] = $fallback_name;
                }
            }
        }

        $club_a = (string) ($club_names[0] ?? 'Bubušinac');
        $club_b = (string) ($club_names[1] ?? 'Napad');
        $liga_slug = sanitize_title((string) ($context['liga_slug'] ?? ''));
        $sezona_slug = sanitize_title((string) ($context['sezona_slug'] ?? ''));
        $liga_label = $liga_slug !== '' ? self::slug_to_title($liga_slug) : 'kvalitetna liga';
        $sezona_label = $sezona_slug !== '' ? str_replace('-', '/', $sezona_slug) : '2025/26';

        $out = [
            $club_a . ' poslednjih 5',
            $club_a . ' sledece 3',
            $club_a . ' vs ' . $club_b,
            $liga_label . ' ' . $sezona_label,
            'koji je ' . $club_a . ' na tabeli',
            'aleksa dimitrijevic ' . $club_a,
        ];

        return array_values(array_unique(array_map('strval', $out)));
    }

    private static function search_parse_intent($query, $limit, array $context)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchIntentParser::parse(
            $query,
            $limit,
            $context,
            static function ($method, array $args) {
                return self::$method(...$args);
            }
        );
    }
    private static function search_compute_form_wl($club_id, array $matches)
    {
        $club_id = intval($club_id);
        $wins = 0;
        $losses = 0;
        foreach ($matches as $m) {
            $home = self::search_resolve_club_id_by_name((string) ($m['homeName'] ?? ''));
            $away = self::search_resolve_club_id_by_name((string) ($m['awayName'] ?? ''));
            $score = trim((string) ($m['scoreLabel'] ?? ''));
            if (!preg_match('/^\s*(\d+)\s*:\s*(\d+)\s*$/', $score, $mm)) {
                continue;
            }
            $hs = intval($mm[1]);
            $as = intval($mm[2]);
            if ($home === $club_id) {
                if ($hs > $as) {
                    $wins++;
                } elseif ($hs < $as) {
                    $losses++;
                }
            } elseif ($away === $club_id) {
                if ($as > $hs) {
                    $wins++;
                } elseif ($as < $hs) {
                    $losses++;
                }
            }
        }
        return ['wins' => $wins, 'losses' => $losses];
    }

    private static function search_resolve_club_id_by_name($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }
        $rows = self::search_posts_by_title('klub', $name, 200);
        foreach ($rows as $row) {
            $id = intval($row['id'] ?? 0);
            if ($id > 0 && self::search_text_score((string) ($row['title'] ?? ''), $name) > 0) {
                return $id;
            }
        }
        return 0;
    }

    private static function search_intent_club_card($club_id, $position)
    {
        $club_id = intval($club_id);
        return [
            'id' => $club_id,
            'title' => self::search_normalize_club_name((string) get_the_title($club_id)),
            'url' => (string) get_permalink($club_id),
            'thumb' => self::search_post_thumb_url($club_id, 'assets/img/fallback-club.png'),
            'positionLabel' => intval($position) > 0 ? ('Trenutno mesto: ' . intval($position) . '.') : 'Mesto nije dostupno.',
        ];
    }

    private static function search_resolve_club_id_by_phrase($phrase, array $context)
    {
        $phrase = trim((string) $phrase);
        if ($phrase === '') {
            return 0;
        }

        $rows = self::search_posts_by_title('klub', $phrase, 1000);
        if (empty($rows)) {
            return 0;
        }

        $competition_club_ids = self::search_competition_club_ids(
            (string) ($context['liga_slug'] ?? ''),
            (string) ($context['sezona_slug'] ?? '')
        );
        $context_clubs = array_filter([
            intval($context['home_club_id'] ?? 0),
            intval($context['away_club_id'] ?? 0),
        ]);

        $best_id = 0;
        $best_score = 0;
        foreach ($rows as $row) {
            $club_id = intval($row['id'] ?? 0);
            $title = (string) ($row['title'] ?? '');
            if ($club_id <= 0 || $title === '') {
                continue;
            }
            $score = max(
                self::search_text_score($title, $phrase),
                self::search_text_score($phrase, $title)
            );
            if ($score <= 0) {
                continue;
            }
            if (in_array($club_id, $context_clubs, true)) {
                $score += 170;
            } elseif (in_array($club_id, $competition_club_ids, true)) {
                $score += 70;
            }
            if ($score > $best_score) {
                $best_score = $score;
                $best_id = $club_id;
            }
        }

        return $best_score > 0 ? $best_id : 0;
    }

    private static function search_resolve_intent_scope_for_club($club_id, array $context)
    {
        $club_id = intval($club_id);
        $liga_slug = sanitize_title((string) ($context['liga_slug'] ?? ''));
        $sezona_slug = sanitize_title((string) ($context['sezona_slug'] ?? ''));
        if ($club_id <= 0) {
            return ['liga_slug' => $liga_slug, 'sezona_slug' => $sezona_slug];
        }
        if ($liga_slug !== '' && $sezona_slug !== '') {
            return ['liga_slug' => $liga_slug, 'sezona_slug' => $sezona_slug];
        }

        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return ['liga_slug' => $liga_slug, 'sezona_slug' => $sezona_slug];
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT liga_slug, sezona_slug
             FROM {$table}
             WHERE (home_club_post_id=%d OR away_club_post_id=%d) AND liga_slug<>'' AND sezona_slug<>''
             ORDER BY match_date DESC, id DESC
             LIMIT 1",
            $club_id,
            $club_id
        ));

        if (is_object($row)) {
            if ($liga_slug === '') {
                $liga_slug = sanitize_title((string) ($row->liga_slug ?? ''));
            }
            if ($sezona_slug === '') {
                $sezona_slug = sanitize_title((string) ($row->sezona_slug ?? ''));
            }
        }

        return ['liga_slug' => $liga_slug, 'sezona_slug' => $sezona_slug];
    }

    private static function search_compute_club_position($club_id, $liga_slug, $sezona_slug)
    {
        $club_id = intval($club_id);
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        if ($club_id <= 0 || $liga_slug === '' || $sezona_slug === '') {
            return 0;
        }

        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return 0;
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT home_club_post_id, away_club_post_id, home_score, away_score, played
             FROM {$table}
             WHERE liga_slug=%s AND sezona_slug=%s
             ORDER BY match_date ASC, id ASC",
            $liga_slug,
            $sezona_slug
        ));
        if (!is_array($rows) || empty($rows)) {
            return 0;
        }

        $stat = [];
        foreach ($rows as $r) {
            $home = intval($r->home_club_post_id ?? 0);
            $away = intval($r->away_club_post_id ?? 0);
            if ($home > 0 && !isset($stat[$home])) {
                $stat[$home] = ['pts' => 0, 'diff' => 0];
            }
            if ($away > 0 && !isset($stat[$away])) {
                $stat[$away] = ['pts' => 0, 'diff' => 0];
            }
        }

        foreach ($rows as $r) {
            if (intval($r->played ?? 0) !== 1) {
                continue;
            }
            $home = intval($r->home_club_post_id ?? 0);
            $away = intval($r->away_club_post_id ?? 0);
            if ($home <= 0 || $away <= 0) {
                continue;
            }
            $hs = intval($r->home_score ?? 0);
            $as = intval($r->away_score ?? 0);
            $stat[$home]['diff'] += ($hs - $as);
            $stat[$away]['diff'] += ($as - $hs);
            if ($hs > $as) {
                $stat[$home]['pts'] += 2;
                $stat[$away]['pts'] += 1;
            } elseif ($as > $hs) {
                $stat[$away]['pts'] += 2;
                $stat[$home]['pts'] += 1;
            }
        }

        uasort($stat, static function ($a, $b) {
            $pts_cmp = intval($b['pts'] ?? 0) <=> intval($a['pts'] ?? 0);
            if ($pts_cmp !== 0) {
                return $pts_cmp;
            }
            return intval($b['diff'] ?? 0) <=> intval($a['diff'] ?? 0);
        });

        $rank = 1;
        foreach ($stat as $id => $row) {
            if (intval($id) === $club_id) {
                return $rank;
            }
            $rank++;
        }

        return 0;
    }

    private static function search_fetch_recent_club_matches($club_id, $limit, $liga_slug, $sezona_slug)
    {
        $club_id = intval($club_id);
        $limit = max(1, min(10, intval($limit)));
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        if ($club_id <= 0) {
            return [];
        }

        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }

        $where = ['(home_club_post_id=%d OR away_club_post_id=%d)', 'played=1'];
        $params = [$club_id, $club_id];
        if ($liga_slug !== '') {
            $where[] = 'liga_slug=%s';
            $params[] = $liga_slug;
        }
        if ($sezona_slug !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona_slug;
        }

        $sql = "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY match_date DESC, id DESC
                LIMIT {$limit}";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $home_id = intval($row->home_club_post_id ?? 0);
            $away_id = intval($row->away_club_post_id ?? 0);
            if ($home_id <= 0 || $away_id <= 0) {
                continue;
            }
            $home = self::search_normalize_club_name((string) get_the_title($home_id));
            $away = self::search_normalize_club_name((string) get_the_title($away_id));
            $items[] = [
                'title' => trim($home . ' vs ' . $away),
                'url' => self::search_match_permalink($row),
                'matchRow' => true,
                'homeName' => $home,
                'awayName' => $away,
                'homeThumb' => self::search_post_thumb_url($home_id, 'assets/img/fallback-club.png'),
                'awayThumb' => self::search_post_thumb_url($away_id, 'assets/img/fallback-club.png'),
                'scoreLabel' => intval($row->home_score ?? 0) . ' : ' . intval($row->away_score ?? 0),
                'leagueLabel' => self::slug_to_title((string) ($row->liga_slug ?? '')),
                'dateLabel' => OpenTT_Unified_Readonly_Helpers::display_match_date((string) ($row->match_date ?? '')),
            ];
        }

        return array_slice($items, 0, $limit);
    }

    private static function search_fetch_upcoming_club_matches($club_id, $limit, $liga_slug, $sezona_slug)
    {
        $club_id = intval($club_id);
        $limit = max(1, min(10, intval($limit)));
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        if ($club_id <= 0) {
            return [];
        }

        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }

        $where = ['(home_club_post_id=%d OR away_club_post_id=%d)', 'played=0'];
        $params = [$club_id, $club_id];
        if ($liga_slug !== '') {
            $where[] = 'liga_slug=%s';
            $params[] = $liga_slug;
        }
        if ($sezona_slug !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona_slug;
        }

        $sql = "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date, match_time
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY match_date ASC, id ASC
                LIMIT {$limit}";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $home_id = intval($row->home_club_post_id ?? 0);
            $away_id = intval($row->away_club_post_id ?? 0);
            $home = self::search_normalize_club_name((string) get_the_title($home_id));
            $away = self::search_normalize_club_name((string) get_the_title($away_id));
            $items[] = [
                'title' => trim($home . ' vs ' . $away),
                'url' => self::search_match_permalink($row),
                'matchRow' => true,
                'homeName' => $home,
                'awayName' => $away,
                'homeThumb' => self::search_post_thumb_url($home_id, 'assets/img/fallback-club.png'),
                'awayThumb' => self::search_post_thumb_url($away_id, 'assets/img/fallback-club.png'),
                'scoreLabel' => trim((string) ($row->match_time ?? '')) !== '' ? trim((string) $row->match_time) : 'Najava',
                'leagueLabel' => self::slug_to_title((string) ($row->liga_slug ?? '')),
                'dateLabel' => OpenTT_Unified_Readonly_Helpers::display_match_date((string) ($row->match_date ?? '')),
            ];
        }
        return array_slice($items, 0, $limit);
    }

    private static function search_fetch_h2h_matches($club_a, $club_b, $limit = 5)
    {
        $club_a = intval($club_a);
        $club_b = intval($club_b);
        $limit = max(1, min(10, intval($limit)));
        if ($club_a <= 0 || $club_b <= 0 || $club_a === $club_b) {
            return [];
        }
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $sql = $wpdb->prepare(
            "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
             FROM {$table}
             WHERE played=1 AND ((home_club_post_id=%d AND away_club_post_id=%d) OR (home_club_post_id=%d AND away_club_post_id=%d))
             ORDER BY match_date DESC, id DESC
             LIMIT {$limit}",
            $club_a,
            $club_b,
            $club_b,
            $club_a
        );
        $rows = $wpdb->get_results($sql);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $home = self::search_normalize_club_name((string) get_the_title(intval($row->home_club_post_id ?? 0)));
            $away = self::search_normalize_club_name((string) get_the_title(intval($row->away_club_post_id ?? 0)));
            $out[] = [
                'title' => trim($home . ' vs ' . $away),
                'url' => self::search_match_permalink($row),
                'matchRow' => true,
                'homeName' => $home,
                'awayName' => $away,
                'homeThumb' => self::search_post_thumb_url(intval($row->home_club_post_id ?? 0), 'assets/img/fallback-club.png'),
                'awayThumb' => self::search_post_thumb_url(intval($row->away_club_post_id ?? 0), 'assets/img/fallback-club.png'),
                'scoreLabel' => intval($row->home_score ?? 0) . ' : ' . intval($row->away_score ?? 0),
                'leagueLabel' => self::slug_to_title((string) ($row->liga_slug ?? '')),
                'dateLabel' => OpenTT_Unified_Readonly_Helpers::display_match_date((string) ($row->match_date ?? '')),
            ];
        }
        return $out;
    }

    private static function search_fetch_h2h_next_match($club_a, $club_b)
    {
        $club_a = intval($club_a);
        $club_b = intval($club_b);
        if ($club_a <= 0 || $club_b <= 0 || $club_a === $club_b) {
            return [];
        }
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date, match_time
             FROM {$table}
             WHERE played=0 AND ((home_club_post_id=%d AND away_club_post_id=%d) OR (home_club_post_id=%d AND away_club_post_id=%d))
             ORDER BY match_date ASC, id ASC
             LIMIT 1",
            $club_a,
            $club_b,
            $club_b,
            $club_a
        ));
        if (!is_object($row)) {
            return [];
        }
        $list = self::search_fetch_upcoming_club_matches($club_a, 10, '', '');
        foreach ($list as $item) {
            if ((string) ($item['url'] ?? '') === self::search_match_permalink($row)) {
                return $item;
            }
        }
        return [];
    }

    private static function search_resolve_competition_from_query($folded_query, array $context)
    {
        if (!preg_match('/(20\d{2})\s*[-\/]\s*(\d{2,4})/u', $folded_query, $m)) {
            return [];
        }
        $season = sanitize_title($m[1] . '-' . substr($m[2], -2));
        $league_phrase = trim(str_replace((string) $m[0], '', $folded_query));
        if ($league_phrase === '') {
            return [];
        }
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT liga_slug, sezona_slug, COUNT(*) AS total
             FROM {$table}
             WHERE sezona_slug=%s
             GROUP BY liga_slug, sezona_slug
             ORDER BY total DESC",
            $season
        ));
        if (!is_array($rows) || empty($rows)) {
            return [];
        }
        $best = null;
        $best_score = 0;
        foreach ($rows as $row) {
            $liga_slug = sanitize_title((string) ($row->liga_slug ?? ''));
            if ($liga_slug === '') {
                continue;
            }
            $score = self::search_text_score(self::slug_to_title($liga_slug) . ' ' . $liga_slug, $league_phrase);
            if ($score > $best_score) {
                $best_score = $score;
                $best = $row;
            }
        }
        if (!$best || $best_score <= 0) {
            return [];
        }
        $liga_slug = sanitize_title((string) ($best->liga_slug ?? ''));
        $sezona_slug = sanitize_title((string) ($best->sezona_slug ?? ''));
        return [
            'ligaSlug' => $liga_slug,
            'sezonaSlug' => $sezona_slug,
            'title' => self::slug_to_title($liga_slug) . ' - ' . self::slug_to_title($sezona_slug),
            'url' => self::search_competition_archive_url($liga_slug, $sezona_slug),
            'meta' => 'Tabela, kola i istaknuti mečevi',
        ];
    }

    private static function search_compute_standings_window($club_id, $liga_slug, $sezona_slug)
    {
        $club_id = intval($club_id);
        $rank = self::search_compute_club_position($club_id, $liga_slug, $sezona_slug);
        if ($rank <= 0) {
            return [];
        }
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT home_club_post_id, away_club_post_id, home_score, away_score, played
             FROM {$table}
             WHERE liga_slug=%s AND sezona_slug=%s",
            sanitize_title((string) $liga_slug),
            sanitize_title((string) $sezona_slug)
        ));
        if (!is_array($rows) || empty($rows)) {
            return [];
        }
        $stat = [];
        foreach ($rows as $r) {
            foreach ([intval($r->home_club_post_id ?? 0), intval($r->away_club_post_id ?? 0)] as $cid) {
                if ($cid > 0 && !isset($stat[$cid])) {
                    $stat[$cid] = ['pts' => 0, 'diff' => 0];
                }
            }
        }
        foreach ($rows as $r) {
            if (intval($r->played ?? 0) !== 1) {
                continue;
            }
            $h = intval($r->home_club_post_id ?? 0);
            $a = intval($r->away_club_post_id ?? 0);
            $hs = intval($r->home_score ?? 0);
            $as = intval($r->away_score ?? 0);
            if ($h <= 0 || $a <= 0) {
                continue;
            }
            $stat[$h]['diff'] += ($hs - $as);
            $stat[$a]['diff'] += ($as - $hs);
            if ($hs > $as) {
                $stat[$h]['pts'] += 2;
                $stat[$a]['pts'] += 1;
            } elseif ($as > $hs) {
                $stat[$a]['pts'] += 2;
                $stat[$h]['pts'] += 1;
            }
        }
        uasort($stat, static function ($x, $y) {
            $c = intval($y['pts'] ?? 0) <=> intval($x['pts'] ?? 0);
            return $c !== 0 ? $c : (intval($y['diff'] ?? 0) <=> intval($x['diff'] ?? 0));
        });
        $ranked = [];
        $i = 1;
        foreach ($stat as $cid => $vals) {
            $ranked[] = [
                'rank' => $i,
                'id' => intval($cid),
                'title' => self::search_normalize_club_name((string) get_the_title(intval($cid))),
                'thumb' => self::search_post_thumb_url(intval($cid), 'assets/img/fallback-club.png'),
                'url' => (string) get_permalink(intval($cid)),
                'isTarget' => intval($cid) === $club_id,
            ];
            $i++;
        }
        $idx = max(0, $rank - 1);
        $start = max(0, $idx - 1);
        $slice = array_slice($ranked, $start, 3);
        if (count($slice) < 3 && $start > 0) {
            $slice = array_slice($ranked, max(0, $start - 1), 3);
        }
        return $slice;
    }

    private static function search_resolve_player_club_intent($query, array $context)
    {
        $query = trim((string) $query);
        if ($query === '') {
            return [];
        }
        $club_id_from_query = self::search_resolve_club_id_by_phrase($query, $context);
        if ($club_id_from_query <= 0) {
            $tokens = self::search_tokenize_text_unicode($query);
            $token_count = count($tokens);
            for ($take = 3; $take >= 1; $take--) {
                if ($token_count < $take) {
                    continue;
                }
                $candidate = implode(' ', array_slice($tokens, -$take));
                $club_id_from_query = self::search_resolve_club_id_by_phrase($candidate, $context);
                if ($club_id_from_query > 0) {
                    break;
                }
            }
        }
        $club_name_from_query = $club_id_from_query > 0
            ? self::search_normalize_club_name((string) get_the_title($club_id_from_query))
            : '';
        $player_phrase = $query;
        if ($club_name_from_query !== '') {
            $player_phrase = trim(str_ireplace($club_name_from_query, '', $query));
        }
        if ($player_phrase === '') {
            $player_phrase = $query;
        }

        $players = self::search_posts_by_title('igrac', $player_phrase, 800);
        if (empty($players)) {
            return [];
        }
        $best_player = 0;
        $best_score = 0;
        foreach ($players as $row) {
            $pid = intval($row['id'] ?? 0);
            $title = (string) ($row['title'] ?? '');
            if ($pid <= 0 || $title === '') {
                continue;
            }
            $score = self::search_text_score($title, $query);
            if ($score <= 0) {
                $score = self::search_text_score($title, $player_phrase);
            }
            $club_id = intval(self::get_player_club_id($pid));
            if ($club_id > 0) {
                $club_name = self::search_normalize_club_name((string) get_the_title($club_id));
                $score += intval(self::search_text_score($club_name, $query) / 2);
                if ($club_id_from_query > 0 && $club_id === $club_id_from_query) {
                    $score += 220;
                }
            }
            if ($score > $best_score) {
                $best_score = $score;
                $best_player = $pid;
            }
        }
        if ($best_player <= 0 || $best_score < 25) {
            return [];
        }
        $club_id = intval(self::get_player_club_id($best_player));
        if ($club_id_from_query > 0 && $club_id > 0 && $club_id !== $club_id_from_query) {
            return [];
        }
        return [
            'type' => 'player_club',
            'label' => 'Igrač i klub',
            'player' => [
                'id' => $best_player,
                'title' => trim((string) get_the_title($best_player)),
                'url' => (string) get_permalink($best_player),
                'thumb' => self::search_post_thumb_url($best_player, 'assets/img/fallback-player.png'),
            ],
            'club' => $club_id > 0 ? [
                'id' => $club_id,
                'title' => self::search_normalize_club_name((string) get_the_title($club_id)),
                'url' => (string) get_permalink($club_id),
                'thumb' => self::search_post_thumb_url($club_id, 'assets/img/fallback-club.png'),
            ] : [],
        ];
    }

    private static function search_resolve_player_id_by_phrase($phrase)
    {
        $phrase = trim((string) $phrase);
        if ($phrase === '') {
            return 0;
        }
        $rows = self::search_posts_by_title('igrac', $phrase, 1000);
        $best = 0;
        $best_score = 0;
        foreach ($rows as $row) {
            $id = intval($row['id'] ?? 0);
            $title = (string) ($row['title'] ?? '');
            if ($id <= 0 || $title === '') {
                continue;
            }
            $score = max(self::search_text_score($title, $phrase), self::search_text_score($phrase, $title));
            if ($score > $best_score) {
                $best_score = $score;
                $best = $id;
            }
        }
        return $best_score > 0 ? $best : 0;
    }

    private static function search_fetch_round_matches($liga_slug, $sezona_slug, $round_no)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        $round_no = max(1, intval($round_no));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
             FROM {$table}
             WHERE liga_slug=%s AND sezona_slug=%s
             ORDER BY match_date DESC, id DESC
             LIMIT 500",
            $liga_slug,
            $sezona_slug
        ));
        if (!is_array($rows)) {
            return [];
        }
        $items = [];
        foreach ($rows as $row) {
            $rn = intval(self::extract_round_no((string) ($row->kolo_slug ?? '')));
            if ($rn !== $round_no) {
                continue;
            }
            $items[] = self::search_build_match_row_item($row);
        }
        return array_values(array_filter($items));
    }

    private static function search_fetch_club_home_away_matches($club_id, $is_home, $limit, $liga_slug, $sezona_slug)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $club_id = intval($club_id);
        $limit = max(1, min(30, intval($limit)));
        $where = [$is_home ? 'home_club_post_id=%d' : 'away_club_post_id=%d'];
        $params = [$club_id];
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        if ($liga_slug !== '') {
            $where[] = 'liga_slug=%s';
            $params[] = $liga_slug;
        }
        if ($sezona_slug !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona_slug;
        }
        $sql = "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY match_date DESC, id DESC
                LIMIT {$limit}";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $item = self::search_build_match_row_item($row);
            if (!empty($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function search_parse_local_date($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            return '';
        }
        $d = intval($m[1]);
        $mo = intval($m[2]);
        $y = intval($m[3]);
        if (!checkdate($mo, $d, $y)) {
            return '';
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    private static function search_parse_month_year_range($month_word, $year)
    {
        $month_word = self::search_fold_text(function_exists('mb_strtolower') ? mb_strtolower((string) $month_word, 'UTF-8') : strtolower((string) $month_word));
        $map = [
            'januar' => 1, 'februar' => 2, 'mart' => 3, 'april' => 4, 'maj' => 5, 'jun' => 6, 'jul' => 7,
            'avgust' => 8, 'septembar' => 9, 'oktobar' => 10, 'novembar' => 11, 'decembar' => 12,
        ];
        if (!isset($map[$month_word])) {
            return [];
        }
        $month = intval($map[$month_word]);
        $year = intval($year);
        if ($year < 2000 || $year > 2100) {
            return [];
        }
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));
        return ['from' => $from, 'to' => $to];
    }

    private static function search_fetch_matches_by_club_and_date_range($club_id, $from, $to, $limit)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $club_id = intval($club_id);
        $limit = max(1, min(60, intval($limit)));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
             FROM {$table}
             WHERE (home_club_post_id=%d OR away_club_post_id=%d) AND match_date >= %s AND match_date <= %s
             ORDER BY match_date DESC, id DESC
             LIMIT {$limit}",
            $club_id,
            $club_id,
            $from,
            $to
        ));
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $item = self::search_build_match_row_item($row);
            if (!empty($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function search_player_stats_summary($player_id)
    {
        global $wpdb;
        $games = self::db_table('games');
        if (!self::table_exists($games)) {
            return '';
        }
        $player_id = intval($player_id);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT home_player_post_id, away_player_post_id, home_sets, away_sets
             FROM {$games}
             WHERE home_player_post_id=%d OR away_player_post_id=%d
             ORDER BY id DESC
             LIMIT 400",
            $player_id,
            $player_id
        ));
        if (!is_array($rows) || empty($rows)) {
            return 'Bez dovoljno podataka.';
        }
        $w = 0; $l = 0;
        foreach ($rows as $r) {
            $hp = intval($r->home_player_post_id ?? 0);
            $ap = intval($r->away_player_post_id ?? 0);
            $hs = intval($r->home_sets ?? 0);
            $as = intval($r->away_sets ?? 0);
            if ($hp === $player_id) {
                if ($hs > $as) { $w++; } elseif ($hs < $as) { $l++; }
            } elseif ($ap === $player_id) {
                if ($as > $hs) { $w++; } elseif ($as < $hs) { $l++; }
            }
        }
        $total = $w + $l;
        $pct = $total > 0 ? round(($w / $total) * 100, 1) : 0;
        return 'Skor: ' . $w . '-' . $l . ' (' . $pct . '%).';
    }

    private static function search_top_players_items($limit, array $context)
    {
        $limit = max(1, min(20, intval($limit)));
        $players = self::search_popular_players_group($limit, $context);
        return is_array($players) ? $players : [];
    }

    private static function search_best_player_for_club_item($club_id, array $context)
    {
        $club_id = intval($club_id);
        if ($club_id <= 0) {
            return [];
        }
        $players = self::search_popular_players_group(40, $context);
        if (!is_array($players) || empty($players)) {
            return [];
        }
        foreach ($players as $item) {
            $pid = intval($item['entityId'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            if (intval(self::get_player_club_id($pid)) === $club_id) {
                return [$item];
            }
        }
        return [];
    }

    private static function search_matches_today_items($limit)
    {
        $today = current_time('Y-m-d');
        return self::search_fetch_matches_by_date_status($today, $today, null, $limit);
    }

    private static function search_live_matches_items($limit)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $limit = max(1, min(50, intval($limit)));
        $rows = $wpdb->get_results("SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date FROM {$table} WHERE live=1 ORDER BY match_date DESC, id DESC LIMIT {$limit}");
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $item = self::search_build_match_row_item($row);
            if (!empty($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function search_fetch_matches_by_date_status($from, $to, $played = null, $limit = 20)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $limit = max(1, min(80, intval($limit)));
        $where = ['match_date >= %s', 'match_date <= %s'];
        $params = [$from, $to];
        if ($played === 0 || $played === 1) {
            $where[] = 'played=%d';
            $params[] = intval($played);
        }
        $sql = "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY match_date DESC, id DESC
                LIMIT {$limit}";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $item = self::search_build_match_row_item($row);
            if (!empty($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function search_matches_by_location_items($location_phrase, $limit)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }
        $location_phrase = trim((string) $location_phrase);
        if ($location_phrase === '') {
            return [];
        }
        $limit = max(1, min(80, intval($limit)));
        $like = '%' . $wpdb->esc_like($location_phrase) . '%';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
             FROM {$table}
             WHERE location LIKE %s
             ORDER BY match_date DESC, id DESC
             LIMIT {$limit}",
            $like
        ));
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $item = self::search_build_match_row_item($row);
            if (!empty($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function search_build_match_row_item($row)
    {
        if (!is_object($row)) {
            return [];
        }
        $home_id = intval($row->home_club_post_id ?? 0);
        $away_id = intval($row->away_club_post_id ?? 0);
        if ($home_id <= 0 || $away_id <= 0) {
            return [];
        }
        $home = self::search_normalize_club_name((string) get_the_title($home_id));
        $away = self::search_normalize_club_name((string) get_the_title($away_id));
        return [
            'title' => trim($home . ' vs ' . $away),
            'url' => self::search_match_permalink($row),
            'matchRow' => true,
            'homeName' => $home,
            'awayName' => $away,
            'homeThumb' => self::search_post_thumb_url($home_id, 'assets/img/fallback-club.png'),
            'awayThumb' => self::search_post_thumb_url($away_id, 'assets/img/fallback-club.png'),
            'scoreLabel' => intval($row->home_score ?? 0) . ' : ' . intval($row->away_score ?? 0),
            'leagueLabel' => self::slug_to_title((string) ($row->liga_slug ?? '')),
            'dateLabel' => OpenTT_Unified_Readonly_Helpers::display_match_date((string) ($row->match_date ?? '')),
        ];
    }

    private static function search_build_query_suggestion($query, array $groups)
    {
        $query = trim((string) $query);
        if ($query === '' || !function_exists('levenshtein')) {
            return '';
        }

        $query_tokens = self::search_tokenize_text_unicode($query);
        if (empty($query_tokens)) {
            return '';
        }

        $candidate_tokens = self::search_collect_suggestion_candidates($groups);
        if (empty($candidate_tokens)) {
            return '';
        }

        $replacement_map = [];
        foreach ($query_tokens as $query_token) {
            $query_token = trim((string) $query_token);
            $query_fold = self::search_fold_text(
                function_exists('mb_strtolower') ? mb_strtolower($query_token, 'UTF-8') : strtolower($query_token)
            );
            $query_len = function_exists('mb_strlen') ? mb_strlen($query_fold, 'UTF-8') : strlen($query_fold);
            if ($query_fold === '' || $query_len < 3 || $query_len > 64) {
                continue;
            }

            $threshold = 2;
            if ($query_len <= 6) {
                $threshold = 1;
            } elseif ($query_len >= 11) {
                $threshold = 3;
            }

            $best = null;
            $best_dist = PHP_INT_MAX;
            foreach ($candidate_tokens as $candidate) {
                $candidate_fold = (string) ($candidate['fold'] ?? '');
                $candidate_raw = (string) ($candidate['raw'] ?? '');
                $candidate_len = function_exists('mb_strlen') ? mb_strlen($candidate_fold, 'UTF-8') : strlen($candidate_fold);
                if ($candidate_fold === '' || $candidate_raw === '' || $candidate_len < 3 || $candidate_len > 64) {
                    continue;
                }
                if ($candidate_fold === $query_fold) {
                    $best_dist = 0;
                    $best = '';
                    break;
                }
                if (abs($candidate_len - $query_len) > $threshold) {
                    continue;
                }

                $dist = levenshtein($query_fold, $candidate_fold);
                if ($dist > $threshold) {
                    continue;
                }
                if ($dist < $best_dist) {
                    $best_dist = $dist;
                    $best = $candidate_raw;
                    if ($best_dist === 1) {
                        break;
                    }
                }
            }

            if (is_string($best) && $best !== '' && $best_dist > 0 && $best_dist <= $threshold) {
                $replacement_map[$query_fold] = $best;
            }
        }

        if (empty($replacement_map)) {
            return '';
        }

        $parts = preg_split('/(\s+)/u', $query, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$query];
        $rebuilt = '';
        foreach ($parts as $part) {
            if (trim((string) $part) === '') {
                $rebuilt .= $part;
                continue;
            }
            $fold = self::search_fold_text(
                function_exists('mb_strtolower') ? mb_strtolower((string) $part, 'UTF-8') : strtolower((string) $part)
            );
            if (isset($replacement_map[$fold])) {
                $rebuilt .= (string) $replacement_map[$fold];
            } else {
                $rebuilt .= (string) $part;
            }
        }

        $suggestion = trim((string) $rebuilt);
        if ($suggestion === '') {
            return '';
        }
        $query_folded_full = self::search_fold_text(
            function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query)
        );
        $suggestion_folded_full = self::search_fold_text(
            function_exists('mb_strtolower') ? mb_strtolower($suggestion, 'UTF-8') : strtolower($suggestion)
        );
        if ($query_folded_full === $suggestion_folded_full) {
            return '';
        }

        return $suggestion;
    }

    private static function search_collect_suggestion_candidates(array $groups)
    {
        $texts = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                foreach (['title', 'homeName', 'awayName', 'meta'] as $field) {
                    $value = trim((string) ($item[$field] ?? ''));
                    if ($value !== '') {
                        $texts[] = $value;
                    }
                }
            }
        }

        if (empty($texts)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($texts as $text) {
            $tokens = self::search_tokenize_text_unicode($text);
            foreach ($tokens as $token) {
                $raw = trim((string) $token);
                if ($raw === '') {
                    continue;
                }
                $fold = self::search_fold_text(
                    function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw)
                );
                if ($fold === '' || isset($seen[$fold])) {
                    continue;
                }
                $seen[$fold] = true;
                $out[] = [
                    'raw' => $raw,
                    'fold' => $fold,
                ];
            }
        }

        return $out;
    }

    private static function build_search_discovery_groups($limit, array $context)
    {
        $groups = [];
        $has_competition_context = (
            trim((string) ($context['liga_slug'] ?? '')) !== ''
            || trim((string) ($context['sezona_slug'] ?? '')) !== ''
        );

        $trending = self::search_recent_trending_group($limit);
        if (!empty($trending)) {
            $groups[] = ['key' => 'trending', 'label' => 'Trending', 'items' => $trending];
        }

        $latest_results = self::search_latest_results_group($limit, $context);
        if (empty($latest_results) && $has_competition_context) {
            $latest_results = self::search_latest_results_group($limit, []);
        }
        if (!empty($latest_results)) {
            $groups[] = ['key' => 'latest_results', 'label' => 'Najnoviji rezultati', 'items' => $latest_results];
        }

        $players = self::search_popular_players_group($limit, $context);
        if (empty($players) && $has_competition_context) {
            $players = self::search_popular_players_group($limit, []);
        }
        if (!empty($players)) {
            $groups[] = ['key' => 'players', 'label' => 'Popularni igrači', 'items' => $players];
        }

        $clubs = self::search_popular_clubs_group($limit, $context);
        if (empty($clubs) && $has_competition_context) {
            $clubs = self::search_popular_clubs_group($limit, []);
        }
        if (!empty($clubs)) {
            $groups[] = ['key' => 'clubs', 'label' => 'Popularni klubovi', 'items' => $clubs];
        }

        return $groups;
    }

    private static function search_latest_results_group($limit, array $context)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }

        $where = ['home_club_post_id > 0', 'away_club_post_id > 0'];
        $params = [];
        $liga = (string) ($context['liga_slug'] ?? '');
        $sezona = (string) ($context['sezona_slug'] ?? '');
        if ($liga !== '') {
            $where[] = 'liga_slug=%s';
            $params[] = $liga;
        }
        if ($sezona !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona;
        }

        $sql = "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, played, match_date
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY match_date DESC, id DESC
                LIMIT 40";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $home_id = intval($row->home_club_post_id ?? 0);
            $away_id = intval($row->away_club_post_id ?? 0);
            if ($home_id <= 0 || $away_id <= 0) {
                continue;
            }
            $home_score = intval($row->home_score ?? 0);
            $away_score = intval($row->away_score ?? 0);
            $is_played = intval($row->played ?? 0) === 1 || $home_score > 0 || $away_score > 0;
            if (!$is_played || ($home_score === 0 && $away_score === 0)) {
                continue;
            }

            $home_name = self::search_normalize_club_name((string) get_the_title($home_id));
            $away_name = self::search_normalize_club_name((string) get_the_title($away_id));
            if ($home_name === '' || $away_name === '') {
                continue;
            }

            $liga_label = self::slug_to_title((string) ($row->liga_slug ?? ''));
            $date_label = OpenTT_Unified_Readonly_Helpers::display_match_date((string) ($row->match_date ?? ''));
            $score_label = $home_score . ' : ' . $away_score;

            $items[] = [
                'score' => intval(strtotime((string) ($row->match_date ?? ''))) ?: 0,
                'title' => trim($home_name . ' vs ' . $away_name),
                'url' => self::search_match_permalink($row),
                'meta' => trim($liga_label . ($date_label !== '' ? ' • ' . $date_label : '')),
                'matchRow' => true,
                'homeName' => $home_name,
                'awayName' => $away_name,
                'homeThumb' => self::search_post_thumb_url($home_id, 'assets/img/fallback-club.png'),
                'awayThumb' => self::search_post_thumb_url($away_id, 'assets/img/fallback-club.png'),
                'scoreLabel' => $score_label,
                'leagueLabel' => $liga_label,
                'dateLabel' => $date_label,
            ];
        }

        return self::finalize_search_items($items, 5);
    }

    private static function search_recent_trending_group($limit)
    {
        $events = get_option(self::OPTION_SEARCH_TRENDING_CLICKS, []);
        if (!is_array($events) || empty($events)) {
            return [];
        }

        $now = time();
        $retention_seconds = 14 * DAY_IN_SECONDS;
        $window_seconds = 5 * DAY_IN_SECONDS;
        $min_ts = $now - $retention_seconds;
        $window_ts = $now - $window_seconds;
        $pruned = [];
        $rank = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $type = sanitize_key((string) ($event['type'] ?? ''));
            $entity_id = intval($event['id'] ?? 0);
            $client = sanitize_key((string) ($event['client'] ?? ''));
            $ts = intval($event['ts'] ?? 0);
            if (($type !== 'player' && $type !== 'club') || $entity_id <= 0 || $ts <= 0) {
                continue;
            }
            if ($ts < $min_ts) {
                continue;
            }
            $pruned[] = ['type' => $type, 'id' => $entity_id, 'client' => $client, 'ts' => $ts];
            if ($ts < $window_ts) {
                continue;
            }
            $key = $type . ':' . $entity_id;
            if (!isset($rank[$key])) {
                $rank[$key] = ['type' => $type, 'id' => $entity_id, 'count' => 0, 'last_ts' => 0];
            }
            $rank[$key]['count']++;
            if ($ts >= intval($rank[$key]['last_ts'])) {
                $rank[$key]['last_ts'] = $ts;
            }
        }

        if (count($pruned) !== count($events)) {
            update_option(self::OPTION_SEARCH_TRENDING_CLICKS, array_slice($pruned, -2000), false);
        }

        if (empty($rank)) {
            return [];
        }

        $rows = array_values($rank);
        usort($rows, static function ($a, $b) {
            $cnt = intval($b['count'] ?? 0) <=> intval($a['count'] ?? 0);
            if ($cnt !== 0) {
                return $cnt;
            }
            return intval($b['last_ts'] ?? 0) <=> intval($a['last_ts'] ?? 0);
        });

        $rows = array_slice($rows, 0, 5);
        $out = [];
        foreach ($rows as $row) {
            $type = sanitize_key((string) ($row['type'] ?? ''));
            $entity_id = intval($row['id'] ?? 0);
            $count = intval($row['count'] ?? 0);
            $item = self::search_trending_entity_item($type, $entity_id, $count);
            if (empty($item)) {
                continue;
            }
            $out[] = $item;
        }

        return $out;
    }

    private static function record_search_trending_click($type, $entity_id, $client_token = '')
    {
        $type = sanitize_key((string) $type);
        $entity_id = intval($entity_id);
        if (($type !== 'player' && $type !== 'club') || $entity_id <= 0) {
            return;
        }

        $client_token = sanitize_key((string) $client_token);
        if ($client_token !== '' && strlen($client_token) > 64) {
            $client_token = substr($client_token, 0, 64);
        }

        $post_type = get_post_type($entity_id);
        if (($type === 'player' && $post_type !== 'igrac') || ($type === 'club' && $post_type !== 'klub')) {
            return;
        }

        $events = get_option(self::OPTION_SEARCH_TRENDING_CLICKS, []);
        if (!is_array($events)) {
            $events = [];
        }

        $now = intval(current_time('timestamp'));
        if ($now <= 0) {
            $now = time();
        }
        $min_ts = $now - (14 * DAY_IN_SECONDS);
        $today_key = wp_date('Y-m-d', $now, wp_timezone());
        $daily_cap_global = 20;
        $daily_cap_client = 3;
        $today_hits_for_entity = 0;
        $today_hits_for_entity_client = 0;
        $pruned = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $event_type = sanitize_key((string) ($event['type'] ?? ''));
            $event_id = intval($event['id'] ?? 0);
            $event_client = sanitize_key((string) ($event['client'] ?? ''));
            $event_ts = intval($event['ts'] ?? 0);
            if (($event_type !== 'player' && $event_type !== 'club') || $event_id <= 0 || $event_ts <= 0) {
                continue;
            }
            if ($event_ts < $min_ts) {
                continue;
            }
            $pruned[] = ['type' => $event_type, 'id' => $event_id, 'ts' => $event_ts];

            $event_day = wp_date('Y-m-d', $event_ts, wp_timezone());
            if ($event_day !== $today_key) {
                continue;
            }
            if ($event_type === $type && $event_id === $entity_id) {
                $today_hits_for_entity++;
                if ($client_token !== '' && $event_client !== '' && $event_client === $client_token) {
                    $today_hits_for_entity_client++;
                }
            }
        }

        if ($today_hits_for_entity >= $daily_cap_global || ($client_token !== '' && $today_hits_for_entity_client >= $daily_cap_client)) {
            if (count($pruned) !== count($events)) {
                update_option(self::OPTION_SEARCH_TRENDING_CLICKS, array_slice($pruned, -2000), false);
            }
            return;
        }

        $pruned[] = [
            'type' => $type,
            'id' => $entity_id,
            'client' => $client_token,
            'ts' => $now,
        ];

        if (count($pruned) > 2000) {
            $pruned = array_slice($pruned, -2000);
        }

        update_option(self::OPTION_SEARCH_TRENDING_CLICKS, $pruned, false);
    }

    private static function search_trending_entity_item($type, $entity_id, $count)
    {
        $type = sanitize_key((string) $type);
        $entity_id = intval($entity_id);
        $count = max(0, intval($count));
        if (($type !== 'player' && $type !== 'club') || $entity_id <= 0) {
            return [];
        }

        $post_type = get_post_type($entity_id);
        if (($type === 'player' && $post_type !== 'igrac') || ($type === 'club' && $post_type !== 'klub')) {
            return [];
        }

        $title = trim((string) get_the_title($entity_id));
        $url = (string) get_permalink($entity_id);
        if ($title === '' || $url === '') {
            return [];
        }

        if ($type === 'player') {
            $club_id = self::get_player_club_id($entity_id);
            $club_name = $club_id > 0 ? (string) get_the_title($club_id) : '';
            $meta = trim(($club_name !== '' ? $club_name . ' • ' : '') . self::search_click_count_label($count));
            $thumb = self::search_post_thumb_url($entity_id, 'assets/img/fallback-player.png');
        } else {
            $meta = self::search_click_count_label($count);
            $thumb = self::search_post_thumb_url($entity_id, 'assets/img/fallback-club.png');
        }

        return [
            'title' => $title,
            'url' => $url,
            'meta' => $meta,
            'thumb' => $thumb,
            'entityType' => $type,
            'entityId' => $entity_id,
        ];
    }

    private static function search_click_count_label($count)
    {
        $count = max(0, intval($count));
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return $count . ' klik';
        }
        if (in_array($mod10, [2, 3, 4], true) && !in_array($mod100, [12, 13, 14], true)) {
            return $count . ' klika';
        }
        return $count . ' klikova';
    }

    private static function search_players_group($query, $limit, array $context, array $competition_club_ids, array $match_player_ids)
    {
        $rows = self::search_posts_by_title('igrac', $query, max(800, $limit * 20));
        if (empty($rows)) {
            return [];
        }

        $context_clubs = array_filter([
            intval($context['home_club_id'] ?? 0),
            intval($context['away_club_id'] ?? 0),
        ]);

        $items = [];
        foreach ($rows as $row) {
            $player_id = intval($row['id'] ?? 0);
            if ($player_id <= 0) {
                continue;
            }

            $club_id = self::get_player_club_id($player_id);
            $club_name = $club_id > 0 ? (string) get_the_title($club_id) : '';
            $score = self::search_text_score((string) ($row['title'] ?? ''), $query);
            if ($score <= 0) {
                continue;
            }
            if ($club_id > 0 && in_array($club_id, $context_clubs, true)) {
                $score += 140;
            } elseif ($club_id > 0 && in_array($club_id, $competition_club_ids, true)) {
                $score += 50;
            }
            if ($player_id > 0 && in_array($player_id, $match_player_ids, true)) {
                $score += 180;
            }

            $items[] = [
                'score' => $score,
                'title' => (string) ($row['title'] ?? ''),
                'url' => (string) ($row['url'] ?? ''),
                'meta' => $club_name,
                'thumb' => self::search_post_thumb_url($player_id, 'assets/img/fallback-player.png'),
                'entityType' => 'player',
                'entityId' => $player_id,
            ];
        }

        return self::finalize_search_items($items, $limit);
    }

    private static function search_popular_players_group($limit, array $context)
    {
        global $wpdb;
        $games = self::db_table('games');
        $matches = self::db_table('matches');
        if (!self::table_exists($games) || !self::table_exists($matches)) {
            return [];
        }

        $where = ['1=1'];
        $params = [];
        $liga = (string) ($context['liga_slug'] ?? '');
        $sezona = (string) ($context['sezona_slug'] ?? '');
        if ($liga !== '') {
            $where[] = 'm.liga_slug=%s';
            $params[] = $liga;
        }
        if ($sezona !== '') {
            $where[] = 'm.sezona_slug=%s';
            $params[] = $sezona;
        }
        $where_sql = implode(' AND ', $where);

        $sql = "
            SELECT player_id, SUM(win) AS wins, COUNT(*) AS games_total
            FROM (
                SELECT g.home_player_post_id AS player_id, CASE WHEN g.home_sets > g.away_sets THEN 1 ELSE 0 END AS win
                FROM {$games} g
                INNER JOIN {$matches} m ON m.id = g.match_id
                WHERE g.home_player_post_id > 0 AND {$where_sql}
                UNION ALL
                SELECT g.away_player_post_id AS player_id, CASE WHEN g.away_sets > g.home_sets THEN 1 ELSE 0 END AS win
                FROM {$games} g
                INNER JOIN {$matches} m ON m.id = g.match_id
                WHERE g.away_player_post_id > 0 AND {$where_sql}
            ) ranked
            GROUP BY player_id
            ORDER BY wins DESC, games_total DESC, player_id ASC
            LIMIT " . intval(max(6, $limit * 4));
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, array_merge($params, $params));
        }
        $rows = $wpdb->get_results($sql);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $player_id = intval($row->player_id ?? 0);
            if ($player_id <= 0) {
                continue;
            }
            $title = (string) get_the_title($player_id);
            $url = (string) get_permalink($player_id);
            if ($title === '' || $url === '') {
                continue;
            }
            $club_id = self::get_player_club_id($player_id);
            $club = $club_id > 0 ? (string) get_the_title($club_id) : '';
            $wins = intval($row->wins ?? 0);

            $items[] = [
                'score' => max(1, $wins),
                'title' => $title,
                'url' => $url,
                'meta' => trim($club !== '' ? ($club . ' • ' . $wins . ' pobeda') : ($wins . ' pobeda')),
                'thumb' => self::search_post_thumb_url($player_id, 'assets/img/fallback-player.png'),
                'entityType' => 'player',
                'entityId' => $player_id,
            ];
        }

        return self::finalize_search_items($items, $limit);
    }

    private static function search_clubs_group($query, $limit, array $context, array $competition_club_ids)
    {
        $rows = self::search_posts_by_title('klub', $query, max(600, $limit * 18));
        if (empty($rows)) {
            return [];
        }

        $context_clubs = array_filter([
            intval($context['home_club_id'] ?? 0),
            intval($context['away_club_id'] ?? 0),
        ]);

        $items = [];
        foreach ($rows as $row) {
            $club_id = intval($row['id'] ?? 0);
            if ($club_id <= 0) {
                continue;
            }
            $score = self::search_text_score((string) ($row['title'] ?? ''), $query);
            if ($score <= 0) {
                continue;
            }
            if (in_array($club_id, $context_clubs, true)) {
                $score += 160;
            } elseif (in_array($club_id, $competition_club_ids, true)) {
                $score += 60;
            }
            $items[] = [
                'score' => $score,
                'title' => (string) ($row['title'] ?? ''),
                'url' => (string) ($row['url'] ?? ''),
                'meta' => '',
                'thumb' => self::search_post_thumb_url($club_id, 'assets/img/fallback-club.png'),
                'entityType' => 'club',
                'entityId' => $club_id,
            ];
        }

        return self::finalize_search_items($items, $limit);
    }

    private static function search_popular_clubs_group($limit, array $context)
    {
        global $wpdb;
        $matches = self::db_table('matches');
        if (!self::table_exists($matches)) {
            return [];
        }

        $where = ['1=1'];
        $params = [];
        $liga = (string) ($context['liga_slug'] ?? '');
        $sezona = (string) ($context['sezona_slug'] ?? '');
        if ($liga !== '') {
            $where[] = 'liga_slug=%s';
            $params[] = $liga;
        }
        if ($sezona !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona;
        }
        $where_sql = implode(' AND ', $where);

        $sql = "
            SELECT club_id, SUM(win) AS wins, COUNT(*) AS matches_total
            FROM (
                SELECT home_club_post_id AS club_id, CASE WHEN home_score > away_score THEN 1 ELSE 0 END AS win
                FROM {$matches}
                WHERE home_club_post_id > 0 AND {$where_sql}
                UNION ALL
                SELECT away_club_post_id AS club_id, CASE WHEN away_score > home_score THEN 1 ELSE 0 END AS win
                FROM {$matches}
                WHERE away_club_post_id > 0 AND {$where_sql}
            ) ranked
            GROUP BY club_id
            ORDER BY wins DESC, matches_total DESC, club_id ASC
            LIMIT " . intval(max(6, $limit * 4));
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, array_merge($params, $params));
        }
        $rows = $wpdb->get_results($sql);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $club_id = intval($row->club_id ?? 0);
            if ($club_id <= 0) {
                continue;
            }
            $title = (string) get_the_title($club_id);
            $url = (string) get_permalink($club_id);
            if ($title === '' || $url === '') {
                continue;
            }
            $wins = intval($row->wins ?? 0);
            $matches_total = intval($row->matches_total ?? 0);
            $items[] = [
                'score' => max(1, $wins),
                'title' => $title,
                'url' => $url,
                'meta' => $wins . ' pobeda • ' . $matches_total . ' mečeva',
                'thumb' => self::search_post_thumb_url($club_id, 'assets/img/fallback-club.png'),
                'entityType' => 'club',
                'entityId' => $club_id,
            ];
        }

        return self::finalize_search_items($items, $limit);
    }

    private static function search_leagues_group($query, $limit, array $context)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }

        $rows = $wpdb->get_results("SELECT liga_slug, sezona_slug, COUNT(*) AS total FROM {$table} WHERE liga_slug<>'' GROUP BY liga_slug, sezona_slug ORDER BY total DESC LIMIT 300");
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $liga_slug = sanitize_title((string) ($row->liga_slug ?? ''));
            $sezona_slug = sanitize_title((string) ($row->sezona_slug ?? ''));
            if ($liga_slug === '') {
                continue;
            }

            $liga_title = self::slug_to_title($liga_slug);
            $sezona_title = $sezona_slug !== '' ? self::slug_to_title($sezona_slug) : '';
            $title = $liga_title;
            if ($sezona_title !== '') {
                $title .= ' - ' . $sezona_title;
            }

            $search_blob = $title . ' ' . $liga_slug . ' ' . $sezona_slug;
            $score = self::search_text_score($search_blob, $query);
            if ($score <= 0) {
                continue;
            }
            if ($liga_slug === (string) ($context['liga_slug'] ?? '')) {
                $score += 80;
            }
            if ($sezona_slug !== '' && $sezona_slug === (string) ($context['sezona_slug'] ?? '')) {
                $score += 70;
            }

            $items[] = [
                'score' => $score,
                'title' => $title,
                'url' => self::search_competition_archive_url($liga_slug, $sezona_slug),
                'meta' => '',
                'thumb' => self::search_league_thumb_url($liga_slug, $sezona_slug),
            ];
        }

        return self::finalize_search_items($items, $limit);
    }

    private static function search_matches_group($query, $limit, array $context)
    {
        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }

        $where = ['1=1'];
        $params = [];

        $liga = (string) ($context['liga_slug'] ?? '');
        $sezona = (string) ($context['sezona_slug'] ?? '');
        if ($liga !== '') {
            $where[] = 'liga_slug=%s';
            $params[] = $liga;
        }
        if ($sezona !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona;
        }

        $sql = "SELECT id, legacy_post_id, liga_slug, sezona_slug, kolo_slug, slug, home_club_post_id, away_club_post_id, home_score, away_score, match_date
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY match_date DESC, id DESC
                LIMIT 200";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $home_id = intval($row->home_club_post_id ?? 0);
            $away_id = intval($row->away_club_post_id ?? 0);
            $home = $home_id > 0 ? self::search_normalize_club_name((string) get_the_title($home_id)) : '';
            $away = $away_id > 0 ? self::search_normalize_club_name((string) get_the_title($away_id)) : '';
            if ($home === '' && $away === '') {
                continue;
            }

            $kolo_label = self::slug_to_title((string) ($row->kolo_slug ?? ''));
            $search_blob = $home . ' ' . $away . ' ' . (string) ($row->liga_slug ?? '') . ' ' . (string) ($row->sezona_slug ?? '') . ' ' . $kolo_label;
            $score = self::search_text_score($search_blob, $query);
            if ($score <= 0) {
                continue;
            }

            if (intval($row->id ?? 0) === intval($context['match_id'] ?? 0)) {
                $score += 220;
            }
            if ($home_id > 0 && ($home_id === intval($context['home_club_id'] ?? 0) || $home_id === intval($context['away_club_id'] ?? 0))) {
                $score += 90;
            }
            if ($away_id > 0 && ($away_id === intval($context['home_club_id'] ?? 0) || $away_id === intval($context['away_club_id'] ?? 0))) {
                $score += 90;
            }

            $date = OpenTT_Unified_Readonly_Helpers::display_match_date((string) ($row->match_date ?? ''));
            $meta = trim($kolo_label . ($date !== '' ? ' - ' . $date : ''));
            $items[] = [
                'score' => $score,
                'title' => trim($home . ' vs ' . $away),
                'url' => self::search_match_permalink($row),
                'meta' => $meta,
                'matchRow' => true,
                'homeName' => $home,
                'awayName' => $away,
                'homeThumb' => self::search_post_thumb_url($home_id, 'assets/img/fallback-club.png'),
                'awayThumb' => self::search_post_thumb_url($away_id, 'assets/img/fallback-club.png'),
                'scoreLabel' => intval($row->home_score ?? 0) . ' : ' . intval($row->away_score ?? 0),
                'leagueLabel' => self::slug_to_title((string) ($row->liga_slug ?? '')),
                'dateLabel' => $date,
            ];
        }

        return self::finalize_search_items($items, $limit);
    }

    private static function search_posts_by_title($post_type, $query, $limit)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchRepository::postsByTitle($post_type, $query, $limit);
    }

    private static function search_text_score($text, $query)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchText::score($text, $query);
    }

    private static function search_fuzzy_score($text_folded, $query_folded)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchText::fuzzyScore($text_folded, $query_folded);
    }

    private static function search_tokenize_text($text)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchText::tokenize($text);
    }

    private static function search_tokenize_text_unicode($text)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchText::tokenizeUnicode($text);
    }

    private static function search_fold_text($value)
    {
        return \OpenTT\Unified\WordPress\FrontendSearchText::fold($value);
    }

    private static function finalize_search_items(array $items, $limit)
    {
        if (empty($items)) {
            return [];
        }

        usort($items, static function ($a, $b) {
            $score_cmp = intval($b['score'] ?? 0) <=> intval($a['score'] ?? 0);
            if ($score_cmp !== 0) {
                return $score_cmp;
            }
            return strnatcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        $final = [];
        $seen = [];
        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($title === '' || $url === '') {
                continue;
            }
            $hash = md5($title . '|' . $url);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $final[] = [
                'title' => $title,
                'url' => $url,
                'meta' => trim((string) ($item['meta'] ?? '')),
                'thumb' => trim((string) ($item['thumb'] ?? '')),
                'entityType' => sanitize_key((string) ($item['entityType'] ?? '')),
                'entityId' => max(0, intval($item['entityId'] ?? 0)),
                'query' => trim((string) ($item['query'] ?? '')),
                'matchRow' => !empty($item['matchRow']),
                'homeName' => trim((string) ($item['homeName'] ?? '')),
                'awayName' => trim((string) ($item['awayName'] ?? '')),
                'homeThumb' => trim((string) ($item['homeThumb'] ?? '')),
                'awayThumb' => trim((string) ($item['awayThumb'] ?? '')),
                'scoreLabel' => trim((string) ($item['scoreLabel'] ?? '')),
                'leagueLabel' => trim((string) ($item['leagueLabel'] ?? '')),
                'dateLabel' => trim((string) ($item['dateLabel'] ?? '')),
            ];
            if (count($final) >= $limit) {
                break;
            }
        }

        return $final;
    }

    private static function search_competition_club_ids($liga_slug, $sezona_slug)
    {
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        if ($liga_slug === '') {
            return [];
        }

        global $wpdb;
        $table = self::db_table('matches');
        if (!self::table_exists($table)) {
            return [];
        }

        $where = ['liga_slug=%s'];
        $params = [$liga_slug];
        if ($sezona_slug !== '') {
            $where[] = 'sezona_slug=%s';
            $params[] = $sezona_slug;
        }

        $home_sql = "SELECT DISTINCT home_club_post_id AS club_id FROM {$table} WHERE " . implode(' AND ', $where) . " AND home_club_post_id > 0";
        $away_sql = "SELECT DISTINCT away_club_post_id AS club_id FROM {$table} WHERE " . implode(' AND ', $where) . " AND away_club_post_id > 0";
        $sql = $home_sql . ' UNION ' . $away_sql . ' LIMIT 500';
        $prepared = $wpdb->prepare($sql, array_merge($params, $params));
        $rows = $wpdb->get_col($prepared);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $rows)));
    }

    private static function search_match_player_ids($match_id)
    {
        $match_id = intval($match_id);
        if ($match_id <= 0) {
            return [];
        }

        global $wpdb;
        $games = self::db_table('games');
        if (!self::table_exists($games)) {
            return [];
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT home_player_post_id, away_player_post_id, home_player2_post_id, away_player2_post_id
             FROM {$games}
             WHERE match_id=%d",
            $match_id
        ));
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = intval($row->home_player_post_id ?? 0);
            $ids[] = intval($row->away_player_post_id ?? 0);
            $ids[] = intval($row->home_player2_post_id ?? 0);
            $ids[] = intval($row->away_player2_post_id ?? 0);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        return $ids;
    }

    private static function search_competition_archive_url($liga_slug, $sezona_slug)
    {
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);
        if ($liga_slug === '') {
            return home_url('/');
        }

        if ((string) get_option('permalink_structure', '') === '') {
            $base = home_url('/');
            $args = ['liga' => $liga_slug];
            if ($sezona_slug !== '') {
                $args['sezona'] = $sezona_slug;
            }
            return add_query_arg($args, $base);
        }

        if ($sezona_slug !== '') {
            return home_url('/liga/' . rawurlencode($liga_slug) . '/' . rawurlencode($sezona_slug) . '/');
        }
        return home_url('/liga/' . rawurlencode($liga_slug) . '/');
    }

    private static function search_match_permalink($row)
    {
        $legacy_id = isset($row->legacy_post_id) ? intval($row->legacy_post_id) : 0;
        if (self::is_legacy_match_cpt_enabled() && $legacy_id > 0 && get_post_type($legacy_id) === 'utakmica') {
            return (string) get_permalink($legacy_id);
        }

        $liga = isset($row->liga_slug) ? sanitize_title((string) $row->liga_slug) : '';
        $sezona = isset($row->sezona_slug) ? sanitize_title((string) $row->sezona_slug) : '';
        $kolo = isset($row->kolo_slug) ? sanitize_title((string) $row->kolo_slug) : '';
        $slug = isset($row->slug) ? sanitize_title((string) $row->slug) : '';
        if ($liga === '' || $kolo === '' || $slug === '') {
            return home_url('/');
        }

        $path = '/' . $liga . '/';
        if ($sezona !== '') {
            $path .= $sezona . '/';
        }
        $path .= $kolo . '/' . $slug . '/';

        return home_url($path);
    }

    private static function search_normalize_club_name($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace(['–', '—'], '-', $decoded);
        $decoded = preg_replace('/\s+/u', ' ', $decoded);

        return trim((string) $decoded);
    }

    private static function search_post_thumb_url($post_id, $fallback_asset = '')
    {
        $post_id = intval($post_id);
        if ($post_id > 0 && function_exists('get_the_post_thumbnail_url')) {
            $thumb = (string) get_the_post_thumbnail_url($post_id, 'thumbnail');
            if ($thumb !== '') {
                return $thumb;
            }
        }

        $fallback_asset = trim((string) $fallback_asset);
        if ($fallback_asset !== '') {
            return self::search_plugin_asset_url($fallback_asset);
        }

        return '';
    }

    private static function search_league_thumb_url($liga_slug, $sezona_slug = '')
    {
        $liga_slug = sanitize_title((string) $liga_slug);
        $sezona_slug = sanitize_title((string) $sezona_slug);

        if ($liga_slug !== '' && $sezona_slug !== '') {
            $rule_id = intval(self::competition_rule_id_by_slugs($liga_slug, $sezona_slug));
            if ($rule_id > 0) {
                $thumb = self::search_post_thumb_url($rule_id);
                if ($thumb !== '') {
                    return $thumb;
                }
            }
        }

        if ($liga_slug !== '') {
            $post = get_page_by_path($liga_slug, OBJECT, 'liga');
            if ($post && !is_wp_error($post)) {
                $thumb = self::search_post_thumb_url((int) $post->ID);
                if ($thumb !== '') {
                    return $thumb;
                }
            }
        }

        return self::search_plugin_asset_url('assets/img/fallback-club.png');
    }

    private static function search_plugin_asset_url($asset_rel)
    {
        $asset_rel = ltrim((string) $asset_rel, '/');
        if ($asset_rel === '') {
            return '';
        }
        if (self::$plugin_file === '') {
            return '';
        }
        return (string) plugins_url($asset_rel, self::$plugin_file);
    }
}

