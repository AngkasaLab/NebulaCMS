<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Database-backed search helpers: native full-text on MySQL/MariaDB/PostgreSQL
 * when term length is sufficient; otherwise safe LIKE fallback (SQLite, short terms).
 */
class ContentSearch
{
    private const FULLTEXT_MIN_LENGTH = 3;

    public static function normalizeTerm(?string $term): ?string
    {
        if ($term === null) {
            return null;
        }

        $trimmed = trim($term);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Escape % and _ for SQL LIKE patterns.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    public static function supportsFullText(): bool
    {
        $driver = DB::connection()->getDriverName();

        return in_array($driver, ['mysql', 'mariadb', 'pgsql'], true);
    }

    public static function shouldUseFullText(?string $normalizedTerm): bool
    {
        if ($normalizedTerm === null || ! self::supportsFullText()) {
            return false;
        }

        return mb_strlen($normalizedTerm) >= self::FULLTEXT_MIN_LENGTH;
    }

    /**
     * Post search: full-text on title + content (indexed), plus excerpt via LIKE when using full-text.
     *
     * @param  Builder<\App\Models\Post>  $query
     */
    public static function applyToPostQuery(Builder $query, ?string $term): void
    {
        $term = self::normalizeTerm($term);
        if ($term === null) {
            return;
        }

        $escaped = self::escapeLike($term);

        $query->where(function (Builder $q) use ($term, $escaped) {
            if (self::shouldUseFullText($term)) {
                $q->whereFullText(['title', 'content'], $term)
                    ->orWhere('excerpt', 'like', '%'.$escaped.'%');
            } else {
                $q->where('title', 'like', '%'.$escaped.'%')
                    ->orWhere('excerpt', 'like', '%'.$escaped.'%')
                    ->orWhere('content', 'like', '%'.$escaped.'%');
            }
        });
    }

    /**
     * Page search: full-text on title + content only (no excerpt column).
     *
     * @param  Builder<\App\Models\Page>  $query
     */
    public static function applyToPageQuery(Builder $query, ?string $term): void
    {
        $term = self::normalizeTerm($term);
        if ($term === null) {
            return;
        }

        $escaped = self::escapeLike($term);

        $query->where(function (Builder $q) use ($term, $escaped) {
            if (self::shouldUseFullText($term)) {
                $q->whereFullText(['title', 'content'], $term);
            } else {
                $q->where('title', 'like', '%'.$escaped.'%')
                    ->orWhere('content', 'like', '%'.$escaped.'%');
            }
        });
    }

    /**
     * Generic multi-column LIKE search with correct grouping (no accidental OR leakage).
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $columns
     */
    public static function applyLikeColumns(Builder $query, ?string $term, array $columns): void
    {
        $term = self::normalizeTerm($term);
        if ($term === null || $columns === []) {
            return;
        }

        $escaped = self::escapeLike($term);
        $pattern = '%'.$escaped.'%';

        $query->where(function (Builder $q) use ($columns, $pattern) {
            foreach ($columns as $i => $column) {
                if ($i === 0) {
                    $q->where($column, 'like', $pattern);
                } else {
                    $q->orWhere($column, 'like', $pattern);
                }
            }
        });
    }
}
