<?php
/**
 * OpenTT - Table Tennis Management Plugin
 * Copyright (C) 2026 Aleksa Dimitrijević
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 */

namespace OpenTT\Unified\WordPress;

final class MigrationActionsManager
{
    public static function handleMigrateLeagueSeasonSlugs($capability, $validationReportOptionKey, callable $validateCallback, callable $migrateCallback)
    {
        self::requireCapability($capability);
        check_admin_referer('opentt_unified_migrate_league_season_slugs');

        $report = $validateCallback();
        update_option((string) $validationReportOptionKey, $report, false);
        if (empty($report['ok'])) {
            $msg = 'Migracija nije pokrenuta: validacija je prijavila probleme.';
            wp_safe_redirect(AdminNoticeManager::buildUrl(admin_url('admin.php?page=stkb-unified-competitions'), 'error', $msg));
            exit;
        }

        $result = $migrateCallback();
        $msg = 'Migracija liga/sezona završena. Lige: ' . (int) ($result['leagues'] ?? 0) . ', sezone: ' . (int) ($result['seasons'] ?? 0) . '.';
        wp_safe_redirect(AdminNoticeManager::buildUrl(admin_url('admin.php?page=stkb-unified-competitions'), 'success', $msg));
        exit;
    }

    public static function handleValidateLeagueSeasonMigration($capability, $validationReportOptionKey, callable $validateCallback)
    {
        self::requireCapability($capability);
        check_admin_referer('opentt_unified_validate_league_season_migration');

        $report = $validateCallback();
        update_option((string) $validationReportOptionKey, $report, false);

        $msg = !empty($report['ok'])
            ? 'Validacija je prošla. Možeš pokrenuti migraciju liga/sezona.'
            : 'Validacija je našla probleme. Reši ih pre migracije.';
        $type = !empty($report['ok']) ? 'success' : 'error';
        wp_safe_redirect(AdminNoticeManager::buildUrl(admin_url('admin.php?page=stkb-unified-competitions'), $type, $msg));
        exit;
    }

    private static function requireCapability($capability)
    {
        if (!current_user_can((string) $capability)) {
            wp_die('Nemaš dozvolu za ovu akciju.');
        }
    }
}
