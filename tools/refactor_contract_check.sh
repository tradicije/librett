#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

assert_literal() {
  local file="$1"
  local value="$2"
  if ! rg -Fq "$value" "$file"; then
    echo "Missing frozen contract value in $file: $value" >&2
    return 1
  fi
}

SHORTCODE_FILE="src/WordPress/ShortcodeRegistrar.php"
ADMIN_FILE="includes/modules/class-opentt-unified-admin-module.php"
CONTENT_FILE="src/WordPress/LegacyContentTypeRegistrar.php"
CORE_FILE="includes/class-opentt-unified-core.php"

shortcodes=(
  opentt_matches_grid opentt_standings_table opentt_match_games opentt_h2h
  opentt_mvp opentt_match_report opentt_match_video opentt_home_club
  opentt_away_club opentt_club opentt_match_teams opentt_top_players
  opentt_players opentt_club_news opentt_player_news opentt_related_posts
  opentt_club_info opentt_competition_info opentt_club_form opentt_player_stats
  opentt_team_stats opentt_player_transfers opentt_player_info
  opentt_competitions opentt_clubs
)

admin_actions=(
  opentt_unified_save_match opentt_unified_delete_match
  opentt_unified_delete_matches_bulk opentt_unified_save_game
  opentt_unified_save_games_batch opentt_unified_delete_game
  opentt_unified_save_set opentt_unified_delete_set opentt_unified_save_club
  opentt_unified_delete_club opentt_unified_delete_clubs_bulk
  opentt_unified_save_player opentt_unified_delete_player
  opentt_unified_delete_players_bulk opentt_unified_save_league
  opentt_unified_delete_league opentt_unified_save_season
  opentt_unified_delete_season opentt_unified_save_competition_rule
  opentt_unified_delete_competition_rule opentt_unified_save_settings
  opentt_unified_delete_all_data opentt_unified_onboarding_action
  opentt_unified_export_data opentt_unified_import_validate
  opentt_unified_import_commit opentt_unified_reset_competition_matches
  opentt_unified_competition_diagnostics opentt_unified_repair_competition_played
)

for value in "${shortcodes[@]}"; do
  assert_literal "$SHORTCODE_FILE" "'$value'"
done

for value in "${admin_actions[@]}"; do
  assert_literal "$ADMIN_FILE" "admin_post_$value"
done

for value in klub igrac liga sezona pravilo_takmicenja liga_sezona kolo; do
  assert_literal "$CONTENT_FILE" "'$value'"
done

for value in opentt_matches opentt_games opentt_sets stkb_matches stkb_games stkb_sets; do
  assert_literal "$CORE_FILE" "'$value'"
done

echo "Frozen shortcode, admin action, content type, taxonomy, and table contracts are present."
