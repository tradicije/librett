<?php

function sanitize_key($value)
{
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $value));
}

function get_permalink($postId)
{
    return 'https://example.test/?p=' . intval($postId);
}

final class OpenTT_Search_Test_Wpdb
{
    public $posts = 'wp_posts';
    public $queries = 0;

    public function prepare($sql, $postType)
    {
        return str_replace('%s', "'" . $postType . "'", $sql);
    }

    public function get_results($sql)
    {
        $this->queries++;
        return [(object) ['ID' => 7, 'post_title' => 'Test klub']];
    }
}

$wpdb = new OpenTT_Search_Test_Wpdb();
require_once dirname(__DIR__) . '/src/WordPress/FrontendSearchRepository.php';

use OpenTT\Unified\WordPress\FrontendSearchRepository;

$first = FrontendSearchRepository::postsByTitle('klub', 'test', 20);
$second = FrontendSearchRepository::postsByTitle('klub', 'drugi izraz', 20);

if ($wpdb->queries !== 1 || $first !== $second || count($first) !== 1) {
    fwrite(STDERR, "Search repository request cache failed.\n");
    exit(1);
}

echo "Search repository query check passed (2 reads, 1 SQL query).\n";
