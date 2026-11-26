@extends('layouts.app')

@section('title', 'Your portfolio matches | jTech Nepal')

@section('content')
@php
    $colors = ['sdg_intervention' => '#0e5c47', 'sdg_indicator' => '#2a5b7a', 'nap' => '#a8611a', 'sovereign' => '#9a3b2c', 'draft' => '#6f736c'];
    $offset = count(config('questionnaire.profile_steps', []));
    $limited = ! $isSample && $results !== [] && (count($results) < 10 || (($results[0]['score'] ?? 100) < 50));
@endphp
<div class="page-shell">
    <div class="u-head">
        <p class="u-eyebrow">Results</p>
        <h1>Portfolios matched to your answers</h1>
        <p>Ranked by the choices you made. Each card shows which criteria earned its score.</p>
    </div>

    @if (! empty($persona))
        <div class="u-banner" role="status"><p><strong>Demo profile: {{ $persona['label'] }}.</strong> {{ $persona['description'] }} This is sample input, not a real visitor. Edit any answer to customise it.</p></div>
    @endif

    @if (($profile['answered'] ?? 0) > 0)
        <section class="u-panel" aria-label="Investor profile">
            <div class="u-panel-head"><h2>Your profile</h2><a class="text-link" href="{{ route('questionnaire.show', ['step' => 1]) }}">Edit answers</a></div>
            <div class="u-panel-body">
                <div>@foreach ($profile['chips'] as $chip)<span class="u-chip">{{ $chip }}</span>@endforeach</div>
                @if ($profile['risk'] && $profile['risk']['confidence'] === 'low')
                    <p class="u-note">Your risk answers pointed in different directions, so treat the risk profile as low confidence.</p>
                @endif
                @foreach (($profile['behavior']['notes'] ?? []) as $note)
                    <p class="u-note">{{ $note }}</p>
                @endforeach
                <p class="u-note">Type and the impact slider adjust criterion weights. A conservative risk profile or high loss aversion increases result diversity. Behavioral notes are illustrative, not a validated test. Horizon and structure describe you but do not yet change ranking, because portfolio records carry no risk or horizon data. Decision support only, not investment advice.</p>
            </div>
        </section>
    @endif

    @if (! empty($isSample))
        <div class="u-banner" role="status"><p>You skipped all the portfolio questions, so this is a broad sample rather than a ranked match. Answer any question to get ranked results.</p></div>
    @endif

    @if ($results === [])
        <section class="u-panel" aria-live="polite">
            <div class="u-panel-head"><h2>No matches yet</h2></div>
            <div class="u-panel-body">
                <p style="margin-top:0">No portfolio had a positive match for these answers and the available data.</p>
                <a class="button" href="{{ route('questionnaire.show', ['step' => 1]) }}">Review answers</a>
                <a class="text-link" style="margin-left:14px" href="{{ route('questionnaire.start') }}">Start over</a>
            </div>
        </section>
    @else
        <h2 class="u-eyebrow" style="font-size:12px">At a glance</h2>
        <dl class="u-stats">
            <div><dt>Items recommended</dt><dd>{{ $headline['items'] }}</dd></div>
            <div><dt>Stated funding need</dt><dd>{{ number_format($headline['need'], 1) }} <small>NRs billion</small></dd>
                <dd><small>{{ $headline['costed'] }} of {{ $headline['items'] }} items have an amount in NRs billion</small></dd></div>
            <div><dt>SDGs covered</dt><dd>{{ $headline['sdgs'] }}</dd></div>
            <div><dt>Data completeness</dt><dd>{{ $headline['completeness'] !== null ? $headline['completeness'].'%' : 'n/a' }}</dd></div>
        </dl>

        @if ($limited)
            <div class="u-banner" role="status">
                <p><strong>Why the list may look thin.</strong>
                    @if (count($results) < 10) Only {{ count($results) }} portfolios matched. @endif
                    @if (($results[0]['score'] ?? 100) < 50) The best score is {{ number_format($results[0]['score'], 0) }}%. @endif
                    @if ($headline['costed'] < $headline['items']) {{ $headline['items'] - $headline['costed'] }} items have no stated amount, so amount fit could not be scored for them. @endif
                    Widening may help:
                    <a class="text-link" href="{{ route('questionnaire.show', ['step' => $offset + 4]) }}">include more collections</a>,
                    <a class="text-link" href="{{ route('questionnaire.show', ['step' => $offset + 1]) }}">pick fewer SDGs</a>.</p>
            </div>
        @endif

        <div class="rs-two">
            @if (($mix['status'] ?? '') === 'ok')
                @php
                    $acc = 0; $stops = [];
                    foreach ($mix['collection_shares'] as $ds => $share) { $from = $acc; $acc += $share; $stops[] = ($colors[$ds] ?? '#6f736c')." {$from}% {$acc}%"; }
                    if ($acc < 100) { $stops[] = "#fff {$acc}% 100%"; }
                @endphp
                <section class="u-panel" aria-labelledby="mix-title">
                    <div class="u-panel-head"><h2 id="mix-title">Suggested mix for your budget</h2><span>Illustration</span></div>
                    <div class="u-panel-body">
                        <div class="rs-donut-row">
                            <div class="rs-donut" style="background:conic-gradient({{ implode(',', $stops) }})" aria-hidden="true"></div>
                            <ul class="rs-legend" style="flex-direction:column">
                                @foreach ($mix['collection_shares'] as $dataset => $share)
                                    <li><i style="background:{{ $colors[$dataset] ?? '#6f736c' }}"></i>{{ $collectionNames[$dataset] ?? $dataset }}: {{ number_format($share, 0) }}% of allocation</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="u-table-wrap">
                            <table class="u-table">
                                <thead><tr><th scope="col">Portfolio</th><th scope="col" class="num">Allocation (NPR)</th><th scope="col" class="num">Share of portfolio total</th></tr></thead>
                                <tbody>
                                    @foreach ($mix['items'] as $item)
                                        <tr>
                                            <td>{{ $item['portfolio']['title'] }}<br><span class="u-tag" data-collection="{{ $item['portfolio']['dataset'] }}">{{ $collectionNames[$item['portfolio']['dataset']] ?? $item['portfolio']['dataset'] }}</span></td>
                                            <td class="num">{{ number_format($item['allocation_npr']) }}</td>
                                            <td class="num">{{ $item['fraction'] < 0.0001 ? '<0.01' : number_format($item['fraction'] * 100, 2) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="u-note">Allocated NPR {{ number_format($mix['allocated']) }} of NPR {{ number_format($mix['budget']) }}. Caps: 25% per portfolio, 40% per collection, so some budget may stay unallocated. Only portfolios with a stated amount in NRs billion are used. This is an illustration, not investment advice.
                            @unless ($mix['sdg_ok']) Fewer than 3 distinct SDGs were available for this mix. @endunless</p>
                    </div>
                </section>
            @elseif (($mix['status'] ?? '') === 'insufficient')
                <section class="u-panel"><div class="u-panel-head"><h2>Suggested mix</h2></div>
                    <div class="u-panel-body"><p class="u-note" style="margin:0">No suggested mix: fewer than 3 of the top results have a stated amount in a known unit.</p></div></section>
            @endif

            @if (! empty($needChart))
                @php
                    $maxNeed = max(array_column($needChart, 'need')) ?: 1;
                @endphp
                <section class="u-panel" aria-labelledby="need-title">
                    <div class="u-panel-head"><h2 id="need-title">Funding need by SDG</h2><span>NRs billion</span></div>
                    <div class="u-panel-body">
                        @foreach ($needChart as $row)
                            <div class="rs-bar">
                                <span><strong>SDG {{ $row['sdg'] }}</strong></span>
                                <span class="rs-track"><i style="width:{{ round($row['need'] / $maxNeed * 100) }}%"></i></span>
                                <span>@if ($row['rows'] === 0) no intervention rows @else {{ number_format($row['need'], 1) }} NRs billion ({{ $row['rows'] }} rows) @endif</span>
                            </div>
                        @endforeach
                        <p class="u-note">Sum of non-overview SDG Intervention amounts. Goals with few records give a thin picture.</p>
                        @if ($sdgCoverage)
                            <p class="u-note"><strong>Coverage:</strong> your top {{ $headline['items'] }} cover {{ $sdgCoverage['covered'] }} of {{ $sdgCoverage['chosen'] }} chosen goals.
                                @if ($sdgCoverage['missing'] !== []) No result for SDG {{ implode(', SDG ', $sdgCoverage['missing']) }}. @endif</p>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        <div class="bx-count">
            <h2>Top {{ count($results) }} matches</h2>
            <p>Scores show how much of what you asked for matched, scaled down when a record lacks data for a question you answered.</p>
        </div>

        <form id="save-form" method="post" action="{{ route('saved.store') }}">
            @csrf
            @if (isset($run_id))<input type="hidden" name="run_id" value="{{ (int) $run_id }}">@endif
        </form>
        @if ($errors->any())<div class="u-banner" role="alert"><p>{{ $errors->first() }}</p></div>@endif

        <form method="get" action="{{ route('compare') }}">
            @foreach ($results as $result)
                @php
                    $portfolio = $result['portfolio'];
                    $score = $result['score'];
                    $band = $score === null ? '' : ($score < 40 ? 'is-low' : ($score < 65 ? 'is-mid' : ''));
                @endphp
                <article class="rs-card">
                    <div class="rs-rank" aria-label="Rank {{ $loop->iteration }}">{{ $loop->iteration }}</div>

                    <div class="rs-main">
                        <div class="rs-meta">
                            <span class="u-tag" data-collection="{{ $portfolio['dataset'] }}">{{ $collectionNames[$portfolio['dataset']] ?? \Illuminate\Support\Str::headline($portfolio['dataset']) }}</span>
                            @if ($portfolio['sdg_num'] !== null)@include('partials.sdg-chip', ['n' => $portfolio['sdg_num']])@endif
                        </div>
                        <h3 class="rs-title"><a href="{{ isset($run_id) ? route('go', [$run_id, $portfolio['portfolio_id']]) : route('portfolios.show', $portfolio['portfolio_id']) }}">{{ $portfolio['title'] }}</a></h3>
                        <p class="rs-sum">{{ $portfolio['summary'] ? \Illuminate\Support\Str::limit($portfolio['summary'], 240) : 'No summary provided.' }}</p>

                        <dl class="rs-facts">
                            <div><dt>Category</dt><dd>{{ $portfolio['category'] ?: 'Not provided' }}</dd></div>
                            <div><dt>Amount</dt><dd>
                                @if ($portfolio['total'] === null) Not yet costed
                                @else {{ number_format((float) $portfolio['total'], 2) }} {{ $amountUnits[$portfolio['dataset']] ?? '(unit not stated in source)' }} @endif
                            </dd></div>
                        </dl>

                        <p class="rs-reason">{{ $result['reason'] }}</p>

                        @if (! empty($result['breakdown']))
                            <div class="rs-seg" role="img" aria-label="Score composition by criterion">
                                @foreach ($result['breakdown'] as $b)
                                    @if ($b['applicable'] && $b['contribution'] > 0)
                                        <span class="seg-{{ in_array($b['key'], ['topic', 'focus_area', 'amount_fit'], true) ? $b['key'] : 'other' }}" style="width:{{ min(100, round($b['contribution'], 1)) }}%"></span>
                                    @endif
                                @endforeach
                            </div>
                            <ul class="rs-legend">
                                @foreach ($result['breakdown'] as $b)
                                    <li class="{{ $b['applicable'] ? '' : 'off' }}"><i class="sw-{{ in_array($b['key'], ['topic', 'focus_area', 'amount_fit'], true) ? $b['key'] : 'other' }}"></i>
                                        {{ $b['label'] }}: {{ $b['applicable'] ? number_format($b['contribution'], 0).' pts' : 'not scored' }}</li>
                                @endforeach
                            </ul>
                            <details>
                                <summary>Why this score?</summary>
                                <ul>
                                    @foreach ($result['breakdown'] as $b)
                                        <li>{{ $b['label'] }}:
                                            @if (! $b['applicable']) not scored ({{ $b['note'] }})
                                            @else {{ number_format($b['contribution'], 0) }} points ({{ $b['note'] }}) @endif</li>
                                    @endforeach
                                </ul>
                                <p class="u-note">Data completeness: {{ round($result['coverage'] * 100) }}%. Rows missing data for a question you answered are scored lower.</p>
                            </details>
                        @endif

                        @if (! empty($result['budget_share']))
                            <p class="u-note">Your budget is about {{ $result['budget_share'] >= 1 ? number_format($result['budget_share'], 1) : '<1' }}% of this portfolio's stated total.</p>
                        @endif

                        <div class="rs-links">
                            <a class="text-link" href="{{ route('portfolios.show', $portfolio['portfolio_id']) }}">View details &rarr;</a>
                            @if (! empty($portfolio['document_url']) && preg_match('#^https?://#i', $portfolio['document_url']))
                                <a class="text-link" href="{{ $portfolio['document_url'] }}" rel="noopener noreferrer">Source document &rarr;</a>
                            @endif
                            @if (isset($run_id))
                                <span class="fb no-print" role="group" aria-label="Feedback">
                                    <button type="button" data-fb="thumbs_up" data-pid="{{ $portfolio['portfolio_id'] }}" aria-label="Helpful" title="Helpful">&#128077;</button>
                                    <button type="button" data-fb="thumbs_down" data-pid="{{ $portfolio['portfolio_id'] }}" aria-label="Not helpful" title="Not helpful">&#128078;</button>
                                    <button type="button" data-fb="save" data-pid="{{ $portfolio['portfolio_id'] }}">Save</button>
                                </span>
                            @endif
                            <label class="rs-cmp no-print" for="sv-{{ $portfolio['portfolio_id'] }}">
                                <input id="sv-{{ $portfolio['portfolio_id'] }}" form="save-form" type="checkbox" name="save[]" value="{{ $portfolio['portfolio_id'] }}"> Select to save
                            </label>
                            <label class="rs-cmp no-print" for="cmp-{{ $portfolio['portfolio_id'] }}">
                                <input id="cmp-{{ $portfolio['portfolio_id'] }}" type="checkbox" name="ids[]" value="{{ $portfolio['portfolio_id'] }}"> Compare
                            </label>
                        </div>
                    </div>

                    <div class="rs-score {{ $band }}" style="--p:{{ round($score ?? 0) }}" @if ($score !== null) aria-label="Match score {{ number_format($score, 0) }} percent" @endif>
                        <span>Match</span>
                        <div class="rs-ring"><strong>{{ $score !== null ? number_format($score, 0).'%' : '—' }}</strong></div>
                        @if (isset($result['coverage']))
                            <div class="rs-meter" aria-hidden="true"><i style="width:{{ round($result['coverage'] * 100) }}%"></i></div>
                            <small>{{ round($result['coverage'] * 100) }}% data</small>
                        @endif
                    </div>
                </article>
            @endforeach

            <div class="rs-tray no-print">
                <span><label><input type="checkbox" id="sv-all"> Select all</label>
                    <strong id="sv-count">0</strong> to save</span>
                <span><strong id="cmp-count">0</strong> selected (pick 2 or 3)</span>
                <span class="u-tray-actions">
                    <button class="button" id="sv-go" type="submit" form="save-form" disabled>Save selected</button>
                    <button class="button" id="cmp-go" type="submit" disabled>Compare selected</button>
                    <a class="text-link" href="{{ route('recommendations.export') }}">Download CSV</a>
                    <button class="button button-secondary" type="button" onclick="window.print()">Print / save as PDF</button>
                    @if (! empty($share_token))<button class="button button-secondary" type="button" data-copy="{{ route('share', $share_token) }}">Copy share link</button>@endif
                </span>
            </div>
        </form>

        @php
            $pts = [];
            foreach ($results as $r) {
                if (! empty($r['portfolio']['total']) && (float) $r['portfolio']['total'] > 0 && ($amountUnits[$r['portfolio']['dataset']] ?? null) === 'NRs billion' && $r['score'] !== null) {
                    $pts[] = ['x' => log10((float) $r['portfolio']['total']), 'y' => (float) $r['score'], 't' => $r['portfolio']['title'], 'a' => (float) $r['portfolio']['total'], 'd' => $r['portfolio']['dataset']];
                }
            }
        @endphp
        @if (count($pts) >= 3)
            @php
                $xs = array_column($pts, 'x'); $mn = min($xs); $mx = max($xs); $span = ($mx - $mn) ?: 1;
            @endphp
            <section class="u-panel" aria-labelledby="sc-title">
                <div class="u-panel-head"><h2 id="sc-title">Score vs amount</h2><span>log scale, NRs billion</span></div>
                <div class="u-panel-body">
                    <svg viewBox="0 0 400 180" role="img" aria-label="Scatter of match score against stated amount for costed results" class="rs-scatter">
                        <line x1="30" y1="150" x2="390" y2="150" stroke="#9aa095"/><line x1="30" y1="10" x2="30" y2="150" stroke="#9aa095"/>
                        <text x="30" y="168" font-size="10" fill="#5b625d">{{ number_format(10 ** $mn, 2) }}</text>
                        <text x="390" y="168" font-size="10" text-anchor="end" fill="#5b625d">{{ number_format(10 ** $mx, 2) }}</text>
                        <text x="4" y="14" font-size="10" fill="#5b625d">100</text><text x="12" y="150" font-size="10" fill="#5b625d">0</text>
                        @foreach ($pts as $p)
                            <circle cx="{{ 40 + ($p['x'] - $mn) / $span * 340 }}" cy="{{ 150 - $p['y'] * 1.4 }}" r="6" fill="{{ $colors[$p['d']] ?? '#6f736c' }}" fill-opacity=".8"><title>{{ $p['t'] }}: {{ round($p['y']) }}%, {{ number_format($p['a'], 2) }} NRs billion</title></circle>
                        @endforeach
                    </svg>
                    <details><summary>Table version</summary>
                        <ul>@foreach ($pts as $p)<li>{{ $p['t'] }}: {{ round($p['y']) }}% match, {{ number_format($p['a'], 2) }} NRs billion</li>@endforeach</ul>
                    </details>
                </div>
            </section>
        @endif

        <p class="u-note">
            <a class="text-link" href="{{ route('questionnaire.show', ['step' => 1]) }}">Review your answers</a> ·
            <a class="text-link" href="{{ route('questionnaire.start') }}">Start a new questionnaire</a>
        </p>
        <p class="u-note">
            Scoring v{{ $algorithm }}. Weights:
            @foreach (['topic' => 'SDG / topic', 'focus_area' => 'Focus area', 'amount_fit' => 'Amount size'] as $key => $label)
                {{ $label }} {{ number_format($weights[$key] ?? 0, 2) }}{{ $loop->last ? '.' : ',' }}
            @endforeach
            Collection is a filter only. <a class="text-link" href="{{ route('method') }}">How scoring works</a>.
        </p>
        <p class="u-note">Illustrative decision support, not financial advice. Amounts are as stated in the source dataset, in the units shown; items without an amount show "Not yet costed".</p>

        @if (isset($run_id))
        <script>
        (function(){var t=document.querySelector('meta[name="csrf-token"]').content,run={{ (int) $run_id }};
        document.querySelectorAll('[data-fb]').forEach(function(el){el.addEventListener('click',function(){
          fetch('{{ route('feedback.store') }}',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':t},
            body:JSON.stringify({run_id:run,portfolio_id:+el.dataset.pid,event:el.dataset.fb})}).then(function(r){
              if(r.ok){el.classList.add('is-done');el.setAttribute('aria-pressed','true');}});});});
        document.querySelectorAll('[data-copy]').forEach(function(el){el.addEventListener('click',function(){
          navigator.clipboard&&navigator.clipboard.writeText(el.dataset.copy).then(function(){el.textContent='Link copied';});});});})();
        </script>
        @endif
        <script>
        (function(){var b=[].slice.call(document.querySelectorAll('input[name="ids[]"]')),n=document.getElementById('cmp-count'),g=document.getElementById('cmp-go');
        function u(){var c=b.filter(function(x){return x.checked}).length;n.textContent=c;g.disabled=c<2||c>3;b.forEach(function(x){x.disabled=!x.checked&&c>=3})}
        b.forEach(function(x){x.addEventListener('change',u)});u();})();
        (function(){var s=[].slice.call(document.querySelectorAll('input[name="save[]"]')),a=document.getElementById('sv-all'),n=document.getElementById('sv-count'),g=document.getElementById('sv-go');
        function u(){var c=s.filter(function(x){return x.checked}).length;n.textContent=c;g.disabled=c<1;a.checked=c===s.length&&c>0}
        a.addEventListener('change',function(){s.forEach(function(x){x.checked=a.checked});u()});
        s.forEach(function(x){x.addEventListener('change',u)});u();})();
        </script>
    @endif
</div>
@endsection