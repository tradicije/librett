<?php

namespace OpenTT\Unified\WordPress;

final class FrontendSearchRepository
{
    private static $postsByType = [];

    public static function postsByTitle($postType, $query, $limit)
    {
        global $wpdb;

        $postType = sanitize_key((string) $postType);
        $query = trim((string) $query);
        $limit = max(1, min(2000, intval($limit)));
        if ($postType === '' || $query === '') {
            return [];
        }

        $cacheKey = $postType . ':' . $limit;
        if (isset(self::$postsByType[$cacheKey])) {
            return self::$postsByType[$cacheKey];
        }

        $sql = $wpdb->prepare(
            "SELECT ID, post_title
             FROM {$wpdb->posts}
             WHERE post_type=%s
               AND post_status='publish'
             ORDER BY post_title ASC
             LIMIT {$limit}",
            $postType
        );
        $rows = $wpdb->get_results($sql);
        if (!is_array($rows) || empty($rows)) {
            self::$postsByType[$cacheKey] = [];
            return [];
        }

        $posts = [];
        foreach ($rows as $row) {
            $postId = intval($row->ID ?? 0);
            if ($postId <= 0) {
                continue;
            }
            $posts[] = [
                'id' => $postId,
                'title' => (string) ($row->post_title ?? ''),
                'url' => (string) get_permalink($postId),
            ];
        }
        self::$postsByType[$cacheKey] = $posts;
        return $posts;
    }
}
