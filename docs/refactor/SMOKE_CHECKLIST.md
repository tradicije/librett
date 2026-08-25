# LibreTT Refactor Smoke Checklist

Use this checklist after every refactor PR.  
Goal: confirm behavior parity while internals change.

## A) Frontend - Core Shortcodes

1. `opentt_matches_grid`
- Loads rows for selected league/season.
- Round filters still work.
- Played/upcoming display remains correct.

2. `opentt_matches_list`
- Round navigation works without refresh.
- `highlight="auto"` still scopes to correct club context.
- `author="false"` hides updater footer.

3. `opentt_standings_table` and `opentt_standings_short`
- Correct standings rows and ordering.
- Short toggle (`Prikaži celu tabelu` / `Sakrij`) still works.

4. `opentt_players`, `opentt_team_stats`, `opentt_club_form`
- On single club with `?opentt_sezona=...`, data changes with season.

## B) Admin - Critical Actions

1. Save/edit match in admin.
2. Save/edit game and sets.
3. Save/edit club and player.
4. Import validate and import commit screens open and function.

## C) User Portal

1. Login/register/profile pages load.
2. Team manager/league admin pages load for authorized users.
3. No redirect loops or 404 on existing routes.

## D) Quick Technical Checks

1. Run `bash tools/refactor_audit.sh` (full PHP lint and frozen contract).
2. Run `php tools/core_load_smoke.php`.
3. Run `php tools/search_text_smoke.php`, `php tools/search_intent_smoke.php`, `php tools/search_date_smoke.php`, and `php tools/search_repository_smoke.php` when search code changes.
4. No fatal errors in debug log during the above flow.
5. No public shortcode tags changed.
6. For optimizations, complete the before/after fields in `PERFORMANCE_BASELINE.md`.

## Pass/Fail Rule

- PR is accepted only if all applicable checks pass.
- If any check fails, rollback or fix before merge.

