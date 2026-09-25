<?php

namespace App\Database;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;

/**
 * PostgreSQL's LIKE is case-sensitive while MySQL's is not. Rewrite
 * `where(col, 'like', ...)` to ILIKE so searches behave the same on Supabase.
 */
class CaseInsensitivePostgresGrammar extends PostgresGrammar
{
    protected function whereBasic(Builder $query, $where)
    {
        if (in_array(strtolower($where['operator']), ['like', 'not like'], true)) {
            $where['operator'] = strtolower($where['operator']) === 'like' ? 'ilike' : 'not ilike';
        }

        return parent::whereBasic($query, $where);
    }
}
