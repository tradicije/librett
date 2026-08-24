<?php

namespace OpenTT\Unified\WordPress;

final class FrontendSearchText
{
    public static function score($text, $query)
    {
        $text = trim((string) $text);
        $query = trim((string) $query);
        if ($text === '' || $query === '') {
            return 0;
        }

        $textLower = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $queryLower = function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);
        $position = function_exists('mb_strpos')
            ? mb_strpos($textLower, $queryLower, 0, 'UTF-8')
            : strpos($textLower, $queryLower);
        $usedFolded = false;

        if ($position === false) {
            $textFolded = self::fold($textLower);
            $queryFolded = self::fold($queryLower);
            $position = function_exists('mb_strpos')
                ? mb_strpos($textFolded, $queryFolded, 0, 'UTF-8')
                : strpos($textFolded, $queryFolded);
            if ($position === false) {
                return self::fuzzyScore($textFolded, $queryFolded);
            }
            $usedFolded = true;
        }

        $score = ((int) $position === 0) ? 80 : 45;
        $queryLength = function_exists('mb_strlen') ? mb_strlen($queryLower, 'UTF-8') : strlen($queryLower);
        $textLength = function_exists('mb_strlen') ? mb_strlen($textLower, 'UTF-8') : strlen($textLower);
        if ($queryLength > 0 && $textLength > 0) {
            $score += min(20, intval(($queryLength / $textLength) * 100));
        }
        return $usedFolded ? $score - 5 : $score;
    }

    public static function fuzzyScore($textFolded, $queryFolded)
    {
        $textFolded = trim((string) $textFolded);
        $queryFolded = trim((string) $queryFolded);
        if ($textFolded === '' || $queryFolded === '' || !function_exists('levenshtein')) {
            return 0;
        }

        $queryLength = function_exists('mb_strlen') ? mb_strlen($queryFolded, 'UTF-8') : strlen($queryFolded);
        if ($queryLength < 5 || $queryLength > 64) {
            return 0;
        }
        $threshold = $queryLength <= 6 ? 1 : ($queryLength >= 11 ? 3 : 2);
        $minimumDistance = PHP_INT_MAX;

        foreach (self::tokenize($textFolded) as $token) {
            $tokenLength = function_exists('mb_strlen') ? mb_strlen($token, 'UTF-8') : strlen($token);
            if ($tokenLength < 3 || $tokenLength > 64 || abs($tokenLength - $queryLength) > $threshold) {
                continue;
            }
            $minimumDistance = min($minimumDistance, levenshtein($queryFolded, $token));
            if ($minimumDistance === 0) {
                break;
            }
        }

        if ($minimumDistance === PHP_INT_MAX || $minimumDistance > $threshold) {
            return 0;
        }
        return max(12, 26 - ($minimumDistance * 6));
    }

    public static function tokenize($text)
    {
        return self::uniqueTokens($text, '/[^a-z0-9]+/i');
    }

    public static function tokenizeUnicode($text)
    {
        return self::uniqueTokens($text, '/[^\p{L}\p{N}]+/u');
    }

    public static function fold($value)
    {
        $map = [
            'č' => 'c', 'ć' => 'c', 'š' => 's', 'ž' => 'z', 'đ' => 'dj',
            'Č' => 'c', 'Ć' => 'c', 'Š' => 's', 'Ž' => 'z', 'Đ' => 'dj',
            'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dz', 'ђ' => 'dj', 'ћ' => 'c',
            'ч' => 'c', 'ш' => 's', 'ж' => 'z', 'Л' => 'l', 'Љ' => 'lj',
            'Њ' => 'nj', 'Џ' => 'dz', 'Ђ' => 'dj', 'Ћ' => 'c', 'Ч' => 'c',
            'Ш' => 's', 'Ж' => 'z',
        ];
        $folded = strtr((string) $value, $map);
        return trim((string) preg_replace('/\s+/u', ' ', $folded));
    }

    private static function uniqueTokens($text, $pattern)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }
        $tokens = array_filter(array_map('trim', preg_split($pattern, $text) ?: []), 'strlen');
        return array_values(array_unique($tokens));
    }
}
