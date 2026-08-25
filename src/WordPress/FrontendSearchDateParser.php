<?php

namespace OpenTT\Unified\WordPress;

final class FrontendSearchDateParser
{
    public static function localDate($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $matches)) {
            return '';
        }

        $day = intval($matches[1]);
        $month = intval($matches[2]);
        $year = intval($matches[3]);
        if (!checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public static function monthYearRange($monthWord, $year)
    {
        $monthWord = function_exists('mb_strtolower')
            ? mb_strtolower((string) $monthWord, 'UTF-8')
            : strtolower((string) $monthWord);
        $monthWord = FrontendSearchText::fold($monthWord);

        $months = [
            'januar' => 1,
            'februar' => 2,
            'mart' => 3,
            'april' => 4,
            'maj' => 5,
            'jun' => 6,
            'jul' => 7,
            'avgust' => 8,
            'septembar' => 9,
            'oktobar' => 10,
            'novembar' => 11,
            'decembar' => 12,
        ];
        if (!isset($months[$monthWord])) {
            return [];
        }

        $year = intval($year);
        if ($year < 2000 || $year > 2100) {
            return [];
        }

        $month = intval($months[$monthWord]);
        $from = sprintf('%04d-%02d-01', $year, $month);
        return [
            'from' => $from,
            'to' => date('Y-m-t', strtotime($from)),
        ];
    }
}
