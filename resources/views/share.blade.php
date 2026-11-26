@extends('layouts.app')
@section('title', 'Shared recommendations | jTech Nepal')
@section('content')
<div class="page-shell">
    <div class="u-head"><p class="u-eyebrow">Shared snapshot</p><h1>Portfolio matches</h1>
        <p>Read-only snapshot generated {{ \Illuminate\Support\Carbon::parse($run->created_at)->toFormattedDateString() }}. Illustrative, not financial advice.</p></div>
    @foreach ($rows as $r)
        @php($p = $portfolios[$r['portfolio_id']] ?? null)
        @continue(! $p)
        <article class="rs-card" style="grid-template-columns:56px minmax(0,1fr) 110px">
            <div class="rs-rank">{{ $r['rank'] }}</div>
            <div class="rs-main">
                <div class="rs-meta"><span class="u-tag" data-collection="{{ $p->dataset }}">{{ $collectionNames[$p->dataset] ?? $p->dataset }}</span>
                    @if ($p->sdg_num)@include('partials.sdg-chip', ['n' => $p->sdg_num])@endif</div>
                <h3 class="rs-title"><a href="{{ route('portfolios.show', $p->portfolio_id) }}">{{ $p->title }}</a></h3>
                <p class="rs-sum">{{ $p->total === null ? 'Not yet costed' : number_format((float) $p->total, 2).' '.($amountUnits[$p->dataset] ?? '(unit not stated)') }}</p>
            </div>
            <div class="rs-score"><span>Match</span><div class="rs-ring" style="--p:{{ round($r['score'] ?? 0) }}"><strong>{{ isset($r['score']) ? round($r['score']).'%' : '—' }}</strong></div></div>
        </article>
    @endforeach
    <p class="u-note">Scoring {{ $run->algorithm_version }}. Link expires {{ $run->expires_at ? \Illuminate\Support\Carbon::parse($run->expires_at)->toFormattedDateString() : 'never' }}.</p>
</div>
@endsection
