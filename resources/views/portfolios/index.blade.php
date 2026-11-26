@extends('layouts.app')

@section('title', 'Browse portfolios | jTech Nepal')

@section('content')
@php
    $active = collect($filters)->filter(fn ($v, $k) => $v !== null && $v !== '' && $k !== 'sort');
@endphp
<div class="page-shell">
    <div class="u-head">
        <p class="u-eyebrow">Portfolio library</p>
        <h1>Browse portfolios</h1>
        <p>Search the source records and narrow them by collection, focus area, SDG or layer.</p>
    </div>

    <form class="bx-filter" method="get" action="{{ route('portfolios.index') }}">
        <div class="bx-grid">
            <div class="bx-field">
                <label for="filter-q">Search title or category</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120" placeholder="e.g. solar, clinics">
            </div>
            <div class="bx-field">
                <label for="filter-dataset">Collection</label>
                <select id="filter-dataset" name="dataset">
                    <option value="">All collections</option>
                    @foreach ($datasets as $dataset)
                        <option value="{{ $dataset }}" @selected(($filters['dataset'] ?? '') === $dataset)>{{ $collectionNames[$dataset] ?? \Illuminate\Support\Str::headline($dataset) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bx-field">
                <label for="filter-focus-area">Focus area</label>
                <select id="filter-focus-area" name="focus_area">
                    <option value="">All focus areas</option>
                    @foreach ($focusAreas as $focusArea)
                        <option value="{{ $focusArea }}" @selected(($filters['focus_area'] ?? '') === $focusArea)>{{ $focusArea }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bx-field">
                <label for="filter-sdg">SDG</label>
                <select id="filter-sdg" name="sdg_num">
                    <option value="">All SDGs</option>
                    @foreach ($sdgNumbers as $sdgNumber)
                        <option value="{{ $sdgNumber }}" @selected((string) ($filters['sdg_num'] ?? '') === (string) $sdgNumber)>SDG {{ $sdgNumber }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bx-field">
                <label for="filter-layer">Layer</label>
                <select id="filter-layer" name="layer">
                    <option value="">All layers</option>
                    @foreach ($layers as $layer)
                        <option value="{{ $layer }}" @selected(($filters['layer'] ?? '') === $layer)>{{ $layer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bx-field">
                <label for="filter-sort">Sort by</label>
                <select id="filter-sort" name="sort">
                    <option value="default" @selected(($filters['sort'] ?? 'default') !== 'amount')>Collection order, largest amount first</option>
                    <option value="amount" @selected(($filters['sort'] ?? '') === 'amount')>Largest amount</option>
                </select>
            </div>
        </div>
        <div class="bx-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="text-link" href="{{ route('portfolios.index') }}">Clear filters</a>
        </div>
    </form>

    @if ($active->isNotEmpty())
        <p>
            @foreach ($active as $key => $value)
                <span class="u-chip">{{ str_replace('_', ' ', $key) }}: {{ $key === 'dataset' ? ($collectionNames[$value] ?? $value) : $value }}</span>
            @endforeach
        </p>
    @endif

    <div class="bx-count">
        <h2>{{ number_format($portfolios->total()) }} portfolios</h2>
        <p>Showing {{ $portfolios->firstItem() ?? 0 }}–{{ $portfolios->lastItem() ?? 0 }} of {{ number_format($portfolios->total()) }}</p>
    </div>

    @if ($portfolios->isEmpty())
        <section class="u-panel" aria-live="polite">
            <div class="u-panel-head"><h2>No portfolios found</h2></div>
            <div class="u-panel-body"><p style="margin:0">Try changing or clearing one of the filters.</p></div>
        </section>
    @else
        <div>
            @foreach ($portfolios as $portfolio)
                <article class="bx-row">
                    <div class="bx-main">
                        <div class="rs-meta">
                            <span class="u-tag" data-collection="{{ $portfolio->dataset }}">{{ $collectionNames[$portfolio->dataset] ?? \Illuminate\Support\Str::headline($portfolio->dataset) }}</span>
                            @if ($portfolio->sdg_num !== null)<span class="u-chip" style="margin:0">SDG {{ $portfolio->sdg_num }}</span>@endif
                            @if ($portfolio->is_overview)<span class="u-chip" style="margin:0">Overview</span>@endif
                        </div>
                        <h3><a href="{{ route('portfolios.show', array_merge(['portfolio' => $portfolio->portfolio_id], request()->query())) }}">{{ $portfolio->title }}</a></h3>
                        <p>{{ $portfolio->summary ? \Illuminate\Support\Str::limit($portfolio->summary, 200) : 'No summary provided.' }}</p>
                        <p class="u-note">{{ $portfolio->category }}@if ($portfolio->focus_area) · {{ $portfolio->focus_area }}@endif</p>
                    </div>
                    @if ($portfolio->total !== null)
                        <div class="bx-amt">{{ number_format((float) $portfolio->total, 2) }}
                            @if (! empty($amountUnits[$portfolio->dataset]))<small>{{ $amountUnits[$portfolio->dataset] }}</small>@endif</div>
                    @else
                        <div class="bx-amt none">Amount not provided</div>
                    @endif
                </article>
            @endforeach
        </div>
        <div style="margin-top:20px">{{ $portfolios->onEachSide(1)->links('pagination.clean') }}</div>
    @endif
</div>
@endsection
