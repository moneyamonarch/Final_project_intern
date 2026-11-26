@extends('layouts.app')

@section('title', 'jTech | Nepal Investment Portfolio Guide')

@section('content')
<div class="page-shell">
    <section class="hx-hero" aria-labelledby="hero-title">
        <div class="hx-copy">
            <p class="u-eyebrow">Nepal portfolio guide</p>
            <h1 id="hero-title">Match Nepal’s public portfolios to what you want to fund.</h1>
            <p>A catalogue of development, climate and digital-sovereignty programmes, ranked against your priorities, budget and horizon, with the working shown for every score.</p>
            <div class="hx-actions">
                <a class="button" href="{{ route('questionnaire.start') }}">Start questionnaire &rarr;</a>
                <a class="text-link" href="{{ route('portfolios.index') }}">Browse all {{ number_format($portfolioCount) }} records</a>
            </div>
            <p class="u-note">About ten short questions. Any can be skipped.</p>
        </div>
        <aside class="hx-preview" aria-label="Illustration of a result">
            <p class="u-eyebrow">Illustration, not live data</p>
            <div class="rs-card" style="grid-template-columns:40px 1fr 80px">
                <div class="rs-rank" style="font-size:20px">1</div>
                <div class="rs-main">
                    <span class="u-tag" data-collection="sdg_intervention">SDG Interventions</span>
                    <p class="rs-title" style="font-size:15px;margin:4px 0">Example portfolio title</p>
                    <div class="rs-seg" aria-hidden="true"><span class="seg-topic" style="width:34%"></span><span class="seg-focus_area" style="width:22%"></span><span class="seg-amount_fit" style="width:12%"></span></div>
                    <ul class="rs-legend"><li><i class="sw-topic"></i>SDG</li><li><i class="sw-focus_area"></i>Focus</li><li><i class="sw-amount_fit"></i>Amount</li></ul>
                </div>
                <div class="rs-score"><span>Match</span><strong style="font-size:24px">68%</strong></div>
            </div>
            <p class="u-note">Every result shows which criteria earned its score and which were not scored for lack of data.</p>
        </aside>
    </section>

    <div class="hx-steps">
        <div class="hx-step"><b>01</b><h3>Describe yourself</h3><p>Who you are, your budget, horizon and appetite for risk.</p></div>
        <div class="hx-step"><b>02</b><h3>Pick what matters</h3><p>SDGs, focus areas and which collections to include.</p></div>
        <div class="hx-step"><b>03</b><h3>Review ranked matches</h3><p>Score breakdowns, funding need by SDG and a suggested mix.</p></div>
    </div>

    <dl class="u-stats">
        <div><dt>Portfolio records</dt><dd>{{ number_format($portfolioCount) }}</dd></div>
        <div><dt>SDGs represented</dt><dd>{{ number_format($sdgCount) }} <small>of 17</small></dd></div>
        <div><dt>Collections</dt><dd>{{ number_format($collectionCount) }}</dd></div>
        @if (! empty($needTotal))
            <div><dt>Stated need, SDG interventions</dt><dd>{{ number_format($needTotal, 1) }} <small>NRs billion</small></dd></div>
        @endif
    </dl>

    @include('partials.personas')

    <section class="u-panel" style="margin-top:28px" aria-labelledby="scope-title">
        <div class="u-panel-head"><h2 id="scope-title">What this is, and is not</h2></div>
        <div class="u-panel-body">
            <p style="margin:0">The records are public programmes and stated funding needs, not tradable assets. There are no market prices or expected returns here, and “risk” means delivery risk. <a class="text-link" href="{{ route('method') }}">Read the method</a>.</p>
        </div>
    </section>
</div>
@endsection
