<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Driver-aware raw SQL fragments so the same code runs on MySQL and
 * PostgreSQL (Supabase). Column arguments are trusted identifiers, never
 * user input.
 */
class Sql
{
    public static function isPgsql(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /** Month number (1-12). */
    public static function month(string $col): string
    {
        return self::isPgsql() ? "EXTRACT(MONTH FROM {$col})::int" : "MONTH({$col})";
    }

    /** Year number. */
    public static function year(string $col): string
    {
        return self::isPgsql() ? "EXTRACT(YEAR FROM {$col})::int" : "YEAR({$col})";
    }

    /** 'YYYY-MM' string. */
    public static function yearMonth(string $col): string
    {
        return self::isPgsql() ? "TO_CHAR({$col}, 'YYYY-MM')" : "DATE_FORMAT({$col}, '%Y-%m')";
    }

    /** 'MM-DD' string. */
    public static function monthDay(string $col): string
    {
        return self::isPgsql() ? "TO_CHAR({$col}, 'MM-DD')" : "DATE_FORMAT({$col}, '%m-%d')";
    }

    /** Date part of a timestamp. */
    public static function date(string $col): string
    {
        return self::isPgsql() ? "CAST({$col} AS DATE)" : "DATE({$col})";
    }

    /** Whole days from $from to $to (like MySQL DATEDIFF($to, $from)). */
    public static function daysBetween(string $from, string $to): string
    {
        return self::isPgsql()
            ? "(CAST({$to} AS DATE) - CAST({$from} AS DATE))"
            : "DATEDIFF({$to}, {$from})";
    }

    /** Whole hours from $from to $to. */
    public static function hoursBetween(string $from, string $to): string
    {
        return self::isPgsql()
            ? "FLOOR(EXTRACT(EPOCH FROM ({$to} - {$from})) / 3600)"
            : "TIMESTAMPDIFF(HOUR, {$from}, {$to})";
    }

    /** ORDER BY expression giving a column a custom value order (MySQL FIELD()). */
    public static function fieldOrder(string $col, array $values): string
    {
        if (! self::isPgsql()) {
            return 'FIELD(' . $col . ', ' . implode(', ', array_map(fn ($v) => "'{$v}'", $values)) . ')';
        }
        $when = '';
        foreach (array_values($values) as $i => $v) {
            $when .= " WHEN '{$v}' THEN {$i}";
        }
        return "CASE {$col}{$when} ELSE " . count($values) . ' END';
    }

    /**
     * Replace the allowed values of an enum column (MySQL ENUM / the CHECK
     * constraint Laravel creates for enum() on PostgreSQL).
     */
    public static function setEnum(string $table, string $col, array $values, ?string $default = null, bool $notNull = false): void
    {
        $list = implode(',', array_map(fn ($v) => "'{$v}'", $values));

        if (self::isPgsql()) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_{$col}_check");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_{$col}_check CHECK ({$col} IN ({$list}))");
            return;
        }

        DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$col} ENUM({$list})"
            . ($notNull ? ' NOT NULL' : '')
            . ($default !== null ? " DEFAULT '{$default}'" : ''));
    }
}
