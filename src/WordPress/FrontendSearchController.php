<?php

namespace OpenTT\Unified\WordPress;

final class FrontendSearchController
{
    public static function handle(array $deps)
    {
        $nonceOk = check_ajax_referer('opentt_frontend_search', 'nonce', false);
        if ($nonceOk === false) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        $trackType = isset($_POST['track_click_type']) ? sanitize_key((string) wp_unslash($_POST['track_click_type'])) : '';
        $trackId = isset($_POST['track_click_id']) ? intval($_POST['track_click_id']) : 0;
        $trackClient = isset($_POST['track_click_client']) ? sanitize_key((string) wp_unslash($_POST['track_click_client'])) : '';
        if ($trackType !== '' && $trackId > 0) {
            call_user_func($deps['record_click'], $trackType, $trackId, $trackClient);
            wp_send_json_success(['tracked' => true]);
        }

        $query = isset($_POST['q']) ? sanitize_text_field((string) wp_unslash($_POST['q'])) : '';
        $query = trim($query);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 6;
        $limit = max(3, min(12, $limit));

        $context = [];
        if (isset($_POST['context'])) {
            $raw = wp_unslash($_POST['context']);
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $context = $decoded;
                }
            } elseif (is_array($raw)) {
                $context = $raw;
            }
        }
        $context = call_user_func($deps['normalize_context'], $context);

        if ($query === '') {
            wp_send_json_success([
                'query' => '',
                'groups' => call_user_func($deps['discovery_groups'], $limit, $context),
                'querySuggestions' => call_user_func($deps['query_suggestions'], $context),
            ]);
        }

        $groups = call_user_func($deps['search_groups'], $query, $limit, $context);
        wp_send_json_success([
            'query' => $query,
            'groups' => $groups,
            'suggestion' => call_user_func($deps['build_suggestion'], $query, $groups),
            'intent' => call_user_func($deps['parse_intent'], $query, $limit, $context),
            'querySuggestions' => call_user_func($deps['query_suggestions'], $context),
        ]);
    }
}
