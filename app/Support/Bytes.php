<?php

namespace App\Support;

final class Bytes
{
    public static function human(?int $bytes): ?string
    {
        if ($bytes === null || $bytes < 1) {
            return null;
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
