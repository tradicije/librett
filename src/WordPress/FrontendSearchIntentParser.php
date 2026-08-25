<?php

namespace OpenTT\Unified\WordPress;

final class FrontendSearchIntentParser
{
    public static function parse($query, $limit, array $context, callable $call)
    {
        $query = trim((string) $query);
        if ($query === '') {
            return [];
        }

        $folded = self::invoke($call, 'search_fold_text', 
            function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query)
        );
        if ($folded === '') {
            return [];
        }

        // 1) Forma kluba
        if (preg_match('/^forma\s+(.+)$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $matches = self::invoke($call, 'search_fetch_recent_club_matches', $club_id, 5, $scope['liga_slug'], $scope['sezona_slug']);
                $wl = self::invoke($call, 'search_compute_form_wl', $club_id, $matches);
                return [
                    'type' => 'club_recent',
                    'label' => 'Forma kluba',
                    'club' => self::invoke($call, 'search_intent_club_card', $club_id, self::invoke($call, 'search_compute_club_position', $club_id, $scope['liga_slug'], $scope['sezona_slug'])),
                    'matches' => $matches,
                    'note' => 'Učinak: ' . $wl['wins'] . 'W - ' . $wl['losses'] . 'L',
                ];
            }
        }

        // 2) Sledeća / poslednja utakmica kluba
        if (preg_match('/^(sledeca|sledeci|sledeća|sledeći)\s+utakmica\s+(.+)$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[2] ?? '')), $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $next = self::invoke($call, 'search_fetch_upcoming_club_matches', $club_id, 1, $scope['liga_slug'], $scope['sezona_slug']);
                return [
                    'type' => 'generic_list',
                    'label' => 'Sledeća utakmica',
                    'items' => $next,
                ];
            }
        }
        if (preg_match('/^poslednja\s+utakmica\s+(.+)$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $last = self::invoke($call, 'search_fetch_recent_club_matches', $club_id, 1, $scope['liga_slug'], $scope['sezona_slug']);
                return [
                    'type' => 'generic_list',
                    'label' => 'Poslednja utakmica',
                    'items' => $last,
                ];
            }
        }

        if (preg_match('/^(.+?)\s+poslednjih\s+(\d{1,2})$/u', $folded, $m)) {
            $club_phrase = trim((string) ($m[1] ?? ''));
            $requested_limit = max(1, min(10, intval($m[2] ?? 5)));
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', $club_phrase, $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $position = self::invoke($call, 'search_compute_club_position', $club_id, $scope['liga_slug'], $scope['sezona_slug']);
                $matches = self::invoke($call, 'search_fetch_recent_club_matches', $club_id, $requested_limit, $scope['liga_slug'], $scope['sezona_slug']);
                return [
                    'type' => 'club_recent',
                    'label' => 'Poslednjih ' . $requested_limit . ' utakmica',
                    'club' => self::invoke($call, 'search_intent_club_card', $club_id, $position),
                    'matches' => $matches,
                ];
            }
        }

        if (preg_match('/^(.+?)\s+(sledece|sledecih|naredne|narednih)\s+(\d{1,2})$/u', $folded, $m)) {
            $club_phrase = trim((string) ($m[1] ?? ''));
            $requested_limit = max(1, min(10, intval($m[3] ?? 3)));
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', $club_phrase, $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $position = self::invoke($call, 'search_compute_club_position', $club_id, $scope['liga_slug'], $scope['sezona_slug']);
                $matches = self::invoke($call, 'search_fetch_upcoming_club_matches', $club_id, $requested_limit, $scope['liga_slug'], $scope['sezona_slug']);
                return [
                    'type' => 'club_next',
                    'label' => 'Sledećih ' . $requested_limit . ' utakmica',
                    'club' => self::invoke($call, 'search_intent_club_card', $club_id, $position),
                    'matches' => $matches,
                ];
            }
        }

        if (preg_match('/^(.+?)\s+vs\.?\s+(.+?)(?:\s+poslednjih\s+(\d{1,2}))?$/u', $folded, $m)) {
            $club_a = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            $club_b = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[2] ?? '')), $context);
            $h2h_limit = max(1, min(10, intval($m[3] ?? 5)));
            if ($club_a > 0 && $club_b > 0 && $club_a !== $club_b) {
                return [
                    'type' => 'club_h2h',
                    'label' => self::invoke($call, 'search_normalize_club_name', (string) get_the_title($club_a)) . ' vs ' . self::invoke($call, 'search_normalize_club_name', (string) get_the_title($club_b)),
                    'h2h' => self::invoke($call, 'search_fetch_h2h_matches', $club_a, $club_b, $h2h_limit),
                    'last' => self::invoke($call, 'search_fetch_h2h_matches', $club_a, $club_b, 1),
                    'next' => self::invoke($call, 'search_fetch_h2h_next_match', $club_a, $club_b),
                ];
            }
        }

        // 3) Liga + sezona + kolo
        if (preg_match('/^(.+?)\s+(20\d{2}\s*[-\/]\s*\d{2,4})\s+(\d{1,2})\.?\s*kolo$/u', $folded, $m)) {
            $competition = self::invoke($call, 'search_resolve_competition_from_query', trim((string) ($m[1] . ' ' . $m[2])), $context);
            if (!empty($competition)) {
                $round_no = max(1, intval($m[3] ?? 0));
                $items = self::invoke($call, 'search_fetch_round_matches', 
                    (string) ($competition['ligaSlug'] ?? ''),
                    (string) ($competition['sezonaSlug'] ?? ''),
                    $round_no
                );
                return [
                    'type' => 'generic_list',
                    'label' => $round_no . '. kolo',
                    'items' => $items,
                    'note' => (string) ($competition['title'] ?? ''),
                ];
            }
        }

        // 4) Klub kao domaćin/gost
        if (preg_match('/^(.+?)\s+kao\s+(domacin|domaćin|gost)$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $role = trim((string) ($m[2] ?? ''));
                $is_home = ($role === 'domacin' || $role === 'domaćin');
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $items = self::invoke($call, 'search_fetch_club_home_away_matches', $club_id, $is_home, 8, $scope['liga_slug'], $scope['sezona_slug']);
                return [
                    'type' => 'generic_list',
                    'label' => $is_home ? 'Kao domaćin' : 'Kao gost',
                    'items' => $items,
                ];
            }
        }

        if (preg_match('/^koji je (.+) na tabeli$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                $position = self::invoke($call, 'search_compute_club_position', $club_id, $scope['liga_slug'], $scope['sezona_slug']);
                $window = self::invoke($call, 'search_compute_standings_window', $club_id, $scope['liga_slug'], $scope['sezona_slug']);
                return [
                    'type' => 'club_position',
                    'label' => 'Pozicija na tabeli',
                    'club' => self::invoke($call, 'search_intent_club_card', $club_id, $position),
                    'competition' => [
                        'ligaLabel' => self::invoke($call, 'slug_to_title', (string) $scope['liga_slug']),
                        'sezonaLabel' => self::invoke($call, 'slug_to_title', (string) $scope['sezona_slug']),
                    ],
                    'standings' => $window,
                ];
            }
        }

        $competition = self::invoke($call, 'search_resolve_competition_from_query', $folded, $context);
        if (!empty($competition)) {
            return [
                'type' => 'league_season',
                'label' => 'Liga i sezona',
                'league' => $competition,
            ];
        }

        // 5) Datumski period i mesec/godina
        if (preg_match('/^(.+?)\s+od\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+do\s+(\d{1,2}\.\d{1,2}\.\d{4})$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $from = self::invoke($call, 'search_parse_local_date', (string) $m[2]);
                $to = self::invoke($call, 'search_parse_local_date', (string) $m[3]);
                if ($from !== '' && $to !== '') {
                    $items = self::invoke($call, 'search_fetch_matches_by_club_and_date_range', $club_id, $from, $to, 20);
                    return ['type' => 'generic_list', 'label' => 'Utakmice u periodu', 'items' => $items];
                }
            }
        }
        if (preg_match('/^(.+?)\s+([a-zčćšđž]+)\s+(20\d{2})$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $range = self::invoke($call, 'search_parse_month_year_range', (string) $m[2], intval($m[3]));
                if (!empty($range)) {
                    $items = self::invoke($call, 'search_fetch_matches_by_club_and_date_range', $club_id, $range['from'], $range['to'], 20);
                    return ['type' => 'generic_list', 'label' => 'Utakmice po datumu', 'items' => $items];
                }
            }
        }

        $player_intent = self::invoke($call, 'search_resolve_player_club_intent', $query, $context);
        if (!empty($player_intent)) {
            return $player_intent;
        }

        // 6) Statistika igrača
        if (preg_match('/^(.+?)\s+skor$/u', $folded, $m) || preg_match('/^(.+?)\s+poslednjih\s+(\d{1,2})\s+partija$/u', $folded, $m)) {
            $player_id = self::invoke($call, 'search_resolve_player_id_by_phrase', $query);
            if ($player_id > 0) {
                $stats = self::invoke($call, 'search_player_stats_summary', $player_id);
                $label = 'Skor igrača';
                return [
                    'type' => 'player_club',
                    'label' => $label,
                    'player' => [
                        'id' => $player_id,
                        'title' => trim((string) get_the_title($player_id)),
                        'url' => (string) get_permalink($player_id),
                        'thumb' => self::invoke($call, 'search_post_thumb_url', $player_id, 'assets/img/fallback-player.png'),
                    ],
                    'club' => [],
                    'note' => $stats,
                ];
            }
        }

        // 7) Rang lista upiti
        if (preg_match('/^top\s+(\d{1,2})\s+igraca$/u', $folded, $m) || preg_match('/^top\s+(\d{1,2})\s+igraca$/u', self::invoke($call, 'search_fold_text', $folded), $m)) {
            $top_n = max(1, min(20, intval($m[1] ?? 10)));
            $items = self::invoke($call, 'search_top_players_items', $top_n, $context);
            return ['type' => 'generic_list', 'label' => 'Top ' . $top_n . ' igrača', 'items' => $items];
        }
        if (preg_match('/^najbolji\s+igrac\s+(.+)$/u', $folded, $m)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $items = self::invoke($call, 'search_best_player_for_club_item', $club_id, $context);
                if (!empty($items)) {
                    return ['type' => 'generic_list', 'label' => 'Najbolji igrač kluba', 'items' => $items];
                }
            }
        }

        // 8) Statusni upiti: danas/live/predstojeće
        if (preg_match('/^utakmice\s+danas$/u', $folded)) {
            return ['type' => 'generic_list', 'label' => 'Utakmice danas', 'items' => self::invoke($call, 'search_matches_today_items', 20)];
        }
        if (preg_match('/^live\s+utakmice$/u', $folded)) {
            return ['type' => 'generic_list', 'label' => 'LIVE utakmice', 'items' => self::invoke($call, 'search_live_matches_items', 20)];
        }
        if (preg_match('/^predstojece\s+(.+)$/u', $folded)) {
            $club_id = self::invoke($call, 'search_resolve_club_id_by_phrase', trim((string) ($m[1] ?? '')), $context);
            if ($club_id > 0) {
                $scope = self::invoke($call, 'search_resolve_intent_scope_for_club', $club_id, $context);
                return [
                    'type' => 'generic_list',
                    'label' => 'Predstojeće utakmice',
                    'items' => self::invoke($call, 'search_fetch_upcoming_club_matches', $club_id, 8, $scope['liga_slug'], $scope['sezona_slug']),
                ];
            }
        }

        // 9) Lokacija
        if (preg_match('/^utakmice\s+u\s+(.+)$/u', $folded, $m)) {
            $items = self::invoke($call, 'search_matches_by_location_items', trim((string) ($m[1] ?? '')), 20);
            if (!empty($items)) {
                return ['type' => 'generic_list', 'label' => 'Utakmice po lokaciji', 'items' => $items];
            }
        }

        return [];
    }


    private static function invoke(callable $call, $method, ...$args)
    {
        return $call((string) $method, $args);
    }
}
