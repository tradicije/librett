<?php

require_once dirname(__DIR__) . '/src/WordPress/FrontendSearchText.php';
require_once dirname(__DIR__) . '/src/WordPress/FrontendSearchIntentParser.php';

use OpenTT\Unified\WordPress\FrontendSearchIntentParser;
use OpenTT\Unified\WordPress\FrontendSearchText;

$calls = [];
$dispatch = static function ($method, array $args) use (&$calls) {
    $calls[] = (string) $method;

    switch ($method) {
        case 'search_fold_text':
            return FrontendSearchText::fold(strtolower((string) ($args[0] ?? '')));
        case 'search_top_players_items':
            return [['id' => 11, 'title' => 'Test igrač']];
        case 'search_resolve_club_id_by_phrase':
            return trim((string) ($args[0] ?? '')) === 'bubusinac' ? 7 : 0;
        case 'search_resolve_intent_scope_for_club':
            return ['liga_slug' => 'prva-liga', 'sezona_slug' => '2026-27'];
        case 'search_fetch_recent_club_matches':
            return [['id' => 21, 'title' => 'Poslednja utakmica']];
        case 'search_fetch_upcoming_club_matches':
            return [['id' => 22, 'title' => 'Sledeća utakmica']];
        case 'search_compute_form_wl':
            return ['wins' => 1, 'losses' => 0];
        case 'search_compute_club_position':
            return 2;
        case 'search_intent_club_card':
            return ['id' => 7, 'position' => 2];
        case 'search_resolve_competition_from_query':
        case 'search_resolve_player_club_intent':
            return [];
        default:
            return null;
    }
};

$top = FrontendSearchIntentParser::parse('top 3 igraca', 6, [], $dispatch);
if (($top['type'] ?? '') !== 'generic_list' || ($top['label'] ?? '') !== 'Top 3 igrača' || count($top['items'] ?? []) !== 1) {
    fwrite(STDERR, "Top-player intent failed.\n");
    exit(1);
}

$form = FrontendSearchIntentParser::parse('forma bubusinac', 6, [], $dispatch);
if (($form['type'] ?? '') !== 'club_recent' || intval($form['club']['id'] ?? 0) !== 7 || intval($form['matches'][0]['id'] ?? 0) !== 21) {
    fwrite(STDERR, 'Club-form intent failed: ' . json_encode($form, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}

$upcoming = FrontendSearchIntentParser::parse('predstojece bubusinac', 6, [], $dispatch);
if (($upcoming['type'] ?? '') !== 'generic_list' || intval($upcoming['items'][0]['id'] ?? 0) !== 22) {
    fwrite(STDERR, 'Upcoming-club intent failed: ' . json_encode($upcoming, JSON_UNESCAPED_UNICODE) . "\n");
    exit(1);
}

if (FrontendSearchIntentParser::parse('', 6, [], $dispatch) !== []) {
    fwrite(STDERR, "Empty-query intent failed.\n");
    exit(1);
}

foreach (['search_fold_text', 'search_top_players_items', 'search_resolve_club_id_by_phrase', 'search_fetch_recent_club_matches', 'search_fetch_upcoming_club_matches'] as $requiredCall) {
    if (!in_array($requiredCall, $calls, true)) {
        fwrite(STDERR, "Missing dispatcher call: {$requiredCall}.\n");
        exit(1);
    }
}

echo "Frontend search intent checks passed.\n";
