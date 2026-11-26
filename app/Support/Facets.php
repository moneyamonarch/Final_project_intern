<?php

namespace App\Support;

use App\Models\Portfolio;
use Illuminate\Support\Facades\Cache;

/** Cached DISTINCT lookups used by the questionnaire and browse filters (M1.7). */
class Facets
{
    private const TTL = 3600;

    public static function datasets(): array
    {
        return Cache::remember('facets.datasets', self::TTL, fn () => Portfolio::query()->select('dataset')->distinct()->orderBy('dataset')->pluck('dataset')->all());
    }

    public static function focusAreas(): array
    {
        return Cache::remember('facets.focus_areas', self::TTL, fn () => Portfolio::query()->select('focus_area')->distinct()->orderBy('focus_area')->pluck('focus_area')->all());
    }

    public static function sdgNumbers(): array
    {
        return Cache::remember('facets.sdgs', self::TTL, fn () => Portfolio::query()->whereNotNull('sdg_num')->select('sdg_num')->distinct()->orderBy('sdg_num')->pluck('sdg_num')->all());
    }

    public static function layers(): array
    {
        return Cache::remember('facets.layers', self::TTL, fn () => Portfolio::query()->whereNotNull('layer')->where('layer', '!=', '')->select('layer')->distinct()->orderBy('layer')->pluck('layer')->all());
    }

    /** Focus areas grouped by focus group, as objects with ->focus_area (what the questionnaire view expects). */
    public static function focusGroups()
    {
        $rows = Cache::remember('facets.focus_groups', self::TTL, fn () => Portfolio::query()->select('focus_group', 'focus_area')->distinct()
            ->orderBy('focus_group')->orderBy('focus_area')->get()->map(fn ($row) => [
                'focus_group' => $row->focus_group,
                'focus_area' => $row->focus_area,
            ])->all());

        return collect($rows)->map(fn ($row) => (object) $row)->groupBy('focus_group');
    }

    /** Call after the portfolio data changes (the importer does). */
    public static function flush(): void
    {
        foreach (['datasets', 'focus_areas', 'sdgs', 'layers', 'focus_groups'] as $key) {
            Cache::forget('facets.'.$key);
        }
    }
}
